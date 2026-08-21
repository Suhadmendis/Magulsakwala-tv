"""
Local Kokoro TTS HTTP server. Exists only to give the PHP backend
(backend/src/Services/KokoroService.php) an HTTP endpoint to call — Kokoro
has no PHP implementation and this project otherwise stays PHP+React (see
CLAUDE.md's documented exception for this one process).

Run: venv/bin/python3 server.py [port]  (default port 8200)
"""
import io
import json
import os
import sys
import wave
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

from kokoro_onnx import Kokoro

MODEL_DIR = os.path.join(os.path.dirname(__file__), "models")
VOICE = os.environ.get("KOKORO_VOICE", "af_sarah")
SPEED = float(os.environ.get("KOKORO_SPEED", "1.0"))

kokoro = Kokoro(
    os.path.join(MODEL_DIR, "kokoro-v1.0.onnx"),
    os.path.join(MODEL_DIR, "voices-v1.0.bin"),
)


def samples_to_wav_bytes(samples, sample_rate):
    buf = io.BytesIO()
    with wave.open(buf, "wb") as w:
        w.setnchannels(1)
        w.setsampwidth(2)
        w.setframerate(sample_rate)
        pcm16 = (samples * 32767).astype("int16")
        w.writeframes(pcm16.tobytes())
    return buf.getvalue()


class Handler(BaseHTTPRequestHandler):
    def log_message(self, fmt, *args):
        sys.stderr.write("%s - %s\n" % (self.address_string(), fmt % args))

    def do_POST(self):
        if self.path != "/tts":
            self.send_response(404)
            self.end_headers()
            return

        length = int(self.headers.get("Content-Length", 0))
        body = self.rfile.read(length)
        try:
            payload = json.loads(body)
            text = payload["text"]
        except (json.JSONDecodeError, KeyError):
            self.send_response(422)
            self.send_header("Content-Type", "application/json")
            self.end_headers()
            self.wfile.write(b'{"error":"text is required"}')
            return

        try:
            samples, sample_rate = kokoro.create(text, voice=VOICE, speed=SPEED, lang="en-us")
            wav_bytes = samples_to_wav_bytes(samples, sample_rate)
        except Exception as e:
            self.send_response(500)
            self.send_header("Content-Type", "application/json")
            self.end_headers()
            self.wfile.write(json.dumps({"error": str(e)}).encode())
            return

        self.send_response(200)
        self.send_header("Content-Type", "audio/wav")
        self.send_header("Content-Length", str(len(wav_bytes)))
        self.end_headers()
        self.wfile.write(wav_bytes)


if __name__ == "__main__":
    port = int(sys.argv[1]) if len(sys.argv) > 1 else 8200
    server = ThreadingHTTPServer(("127.0.0.1", port), Handler)
    print(f"Kokoro TTS server listening on http://127.0.0.1:{port} (voice={VOICE})")
    server.serve_forever()
