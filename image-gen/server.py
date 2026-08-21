"""
Local SD-Turbo image generation HTTP server. Same role as kokoro-tts/server.py
— gives the PHP backend an HTTP endpoint to call, since there's no PHP
implementation of Stable Diffusion (documented exception in CLAUDE.md).

Endpoints:
  GET  /info      -> JSON describing the model + every supported parameter
  POST /generate  -> {prompt, negative_prompt?, steps?, guidance_scale?,
                       width?, height?, seed?, num_images?} -> {"images": [base64 png, ...]}
  POST /img2img   -> {prompt, image (base64 png/jpeg), negative_prompt?,
                       strength?, steps?, guidance_scale?, seed?, num_images?}
                      -> {"images": [base64 png, ...]}

Run: venv/bin/python3 server.py [port]  (default port 8300)
"""
import base64
import io
import json
import sys
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

import torch
from diffusers import AutoPipelineForImage2Image, AutoPipelineForText2Image

MODEL_ID = "stabilityai/sd-turbo"
DEVICE = "mps" if torch.backends.mps.is_available() else "cpu"

txt2img = AutoPipelineForText2Image.from_pretrained(
    MODEL_ID, torch_dtype=torch.float16, variant="fp16"
).to(DEVICE)

# Shares the already-loaded weights instead of loading the checkpoint twice.
img2img = AutoPipelineForImage2Image.from_pipe(txt2img).to(DEVICE)

INFO = {
    "model_id": MODEL_ID,
    "device": DEVICE,
    "description": "SD-Turbo: distilled Stable Diffusion, generates in as little as 1 step. No built-in safety/NSFW filter.",
    "operations": {
        "generate": {
            "endpoint": "POST /generate",
            "description": "Text-to-image.",
            "parameters": {
                "prompt": "string, required — what to generate",
                "negative_prompt": "string, optional — what to avoid (has little/no effect when guidance_scale=0, the turbo default)",
                "steps": "int, default 1 — inference steps; SD-Turbo is designed for 1-4",
                "guidance_scale": "float, default 0.0 — classifier-free guidance strength; SD-Turbo is trained for 0.0 (no CFG); raising it enables negative_prompt but can distort results",
                "width": "int, default 512 — must be a multiple of 8",
                "height": "int, default 512 — must be a multiple of 8",
                "seed": "int, optional — fixes randomness for reproducible output; omit for a random image each call",
                "num_images": "int, default 1 — how many images to generate in one call",
            },
        },
        "img2img": {
            "endpoint": "POST /img2img",
            "description": "Image-to-image — takes a starting image and a prompt, generates a new image influenced by both.",
            "parameters": {
                "prompt": "string, required",
                "image": "string, required — base64-encoded PNG/JPEG input image",
                "negative_prompt": "string, optional (see generate)",
                "strength": "float, default 0.5, range 0-1 — how much to deviate from the input image (1.0 = ignore it almost entirely)",
                "steps": "int, default 2 — effective steps is ceil(steps * strength), SD-Turbo needs at least 1 effective step",
                "guidance_scale": "float, default 0.0 (see generate)",
                "seed": "int, optional",
                "num_images": "int, default 1",
            },
        },
    },
}


def decode_images_to_json(images):
    encoded = []
    for image in images:
        buf = io.BytesIO()
        image.save(buf, format="PNG")
        encoded.append(base64.b64encode(buf.getvalue()).decode("ascii"))
    return json.dumps({"images": encoded}).encode()


def make_generator(seed):
    if seed is None:
        return None
    return torch.Generator(device=DEVICE).manual_seed(int(seed))


class Handler(BaseHTTPRequestHandler):
    def log_message(self, fmt, *args):
        sys.stderr.write("%s - %s\n" % (self.address_string(), fmt % args))

    def _json_error(self, code, message):
        self.send_response(code)
        self.send_header("Content-Type", "application/json")
        self.end_headers()
        self.wfile.write(json.dumps({"error": message}).encode())

    def _read_json_body(self):
        length = int(self.headers.get("Content-Length", 0))
        return json.loads(self.rfile.read(length))

    def do_GET(self):
        if self.path != "/info":
            self.send_response(404)
            self.end_headers()
            return
        self.send_response(200)
        self.send_header("Content-Type", "application/json")
        self.end_headers()
        self.wfile.write(json.dumps(INFO).encode())

    def do_POST(self):
        if self.path == "/generate":
            self._handle_generate()
        elif self.path == "/img2img":
            self._handle_img2img()
        else:
            self.send_response(404)
            self.end_headers()

    def _handle_generate(self):
        try:
            payload = self._read_json_body()
            prompt = payload["prompt"]
        except (json.JSONDecodeError, KeyError):
            self._json_error(422, "prompt is required")
            return

        try:
            result = txt2img(
                prompt=prompt,
                negative_prompt=payload.get("negative_prompt") or None,
                num_inference_steps=int(payload.get("steps", 1)),
                guidance_scale=float(payload.get("guidance_scale", 0.0)),
                width=int(payload.get("width", 512)),
                height=int(payload.get("height", 512)),
                num_images_per_prompt=int(payload.get("num_images", 1)),
                generator=make_generator(payload.get("seed")),
            )
            body = decode_images_to_json(result.images)
        except Exception as e:
            self._json_error(500, str(e))
            return

        self.send_response(200)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def _handle_img2img(self):
        from PIL import Image

        try:
            payload = self._read_json_body()
            prompt = payload["prompt"]
            input_bytes = base64.b64decode(payload["image"])
            input_image = Image.open(io.BytesIO(input_bytes)).convert("RGB")
        except (json.JSONDecodeError, KeyError, Exception) as e:
            self._json_error(422, f"prompt and a valid base64 image are required ({e})")
            return

        try:
            result = img2img(
                prompt=prompt,
                image=input_image,
                negative_prompt=payload.get("negative_prompt") or None,
                strength=float(payload.get("strength", 0.5)),
                num_inference_steps=int(payload.get("steps", 2)),
                guidance_scale=float(payload.get("guidance_scale", 0.0)),
                num_images_per_prompt=int(payload.get("num_images", 1)),
                generator=make_generator(payload.get("seed")),
            )
            body = decode_images_to_json(result.images)
        except Exception as e:
            self._json_error(500, str(e))
            return

        self.send_response(200)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)


if __name__ == "__main__":
    port = int(sys.argv[1]) if len(sys.argv) > 1 else 8300
    server = ThreadingHTTPServer(("127.0.0.1", port), Handler)
    print(f"Image-gen (SD-Turbo) server listening on http://127.0.0.1:{port} (device={DEVICE})")
    server.serve_forever()
