function ImageGenPlayground() {
  const [info, setInfo] = useState(null);
  const [error, setError] = useState(null);

  const [mode, setMode] = useState('generate'); // 'generate' | 'img2img'
  const [prompt, setPrompt] = useState('');
  const [negativePrompt, setNegativePrompt] = useState('');
  const [steps, setSteps] = useState('');
  const [guidanceScale, setGuidanceScale] = useState('');
  const [width, setWidth] = useState('512');
  const [height, setHeight] = useState('512');
  const [seed, setSeed] = useState('');
  const [numImages, setNumImages] = useState('1');
  const [strength, setStrength] = useState('0.5');
  const [sourceFile, setSourceFile] = useState(null);

  const [generating, setGenerating] = useState(false);
  const [resultImages, setResultImages] = useState([]);

  useEffect(() => {
    Api.system.imageGenInfo().then(setInfo).catch((e) => setError(e.message));
  }, []);

  const generate = () => {
    if (!prompt.trim()) {
      setError('Enter a prompt first.');
      return;
    }
    if (mode === 'img2img' && !sourceFile) {
      setError('Choose a starting image first.');
      return;
    }

    setGenerating(true);
    setError(null);
    setResultImages([]);

    const call = mode === 'generate'
      ? Api.system.imageGenGenerate({
          prompt,
          negative_prompt: negativePrompt || undefined,
          steps: steps || undefined,
          guidance_scale: guidanceScale || undefined,
          width: Number(width),
          height: Number(height),
          seed: seed || undefined,
          num_images: Number(numImages),
        })
      : (() => {
          const form = new FormData();
          form.append('prompt', prompt);
          if (negativePrompt) form.append('negative_prompt', negativePrompt);
          if (steps) form.append('steps', steps);
          if (guidanceScale) form.append('guidance_scale', guidanceScale);
          if (seed) form.append('seed', seed);
          form.append('num_images', numImages);
          form.append('strength', strength);
          form.append('image', sourceFile);
          return Api.system.imageGenImg2Img(form);
        })();

    call
      .then((res) => setResultImages(res.images || []))
      .catch((e) => setError(e.message))
      .finally(() => setGenerating(false));
  };

  return (
    <>
      <div className="page-title" style={{ fontSize: 16 }}>Model info</div>
      <div className="card">
        {info ? (
          <pre className="mono" style={{ margin: 0, whiteSpace: 'pre-wrap', wordBreak: 'break-all', fontSize: 12 }}>
            {JSON.stringify(info, null, 2)}
          </pre>
        ) : (
          <div className="empty-state">Loading…</div>
        )}
      </div>

      <div className="page-title" style={{ fontSize: 16 }}>Try it</div>
      <div className="card">
        <div className="field">
          <label>Operation</label>
          <select value={mode} onChange={(e) => setMode(e.target.value)}>
            <option value="generate">generate (text-to-image)</option>
            <option value="img2img">img2img (image-to-image)</option>
          </select>
        </div>

        {mode === 'img2img' && (
          <div className="field">
            <label>Starting image</label>
            <input type="file" accept="image/*" onChange={(e) => setSourceFile(e.target.files[0] || null)} />
          </div>
        )}

        <div className="field">
          <label>Prompt</label>
          <textarea rows="2" value={prompt} onChange={(e) => setPrompt(e.target.value)} />
        </div>
        <div className="field">
          <label>Negative prompt</label>
          <input value={negativePrompt} onChange={(e) => setNegativePrompt(e.target.value)} />
        </div>

        {mode === 'img2img' && (
          <div className="field">
            <label>Strength (0-1)</label>
            <input value={strength} onChange={(e) => setStrength(e.target.value)} />
          </div>
        )}

        <div className="thumbnail-slots">
          <div className="field">
            <label>Steps</label>
            <input value={steps} onChange={(e) => setSteps(e.target.value)} placeholder={mode === 'generate' ? '1' : '2'} />
          </div>
          <div className="field">
            <label>Guidance scale</label>
            <input value={guidanceScale} onChange={(e) => setGuidanceScale(e.target.value)} placeholder="0.0" />
          </div>
          <div className="field">
            <label>Seed</label>
            <input value={seed} onChange={(e) => setSeed(e.target.value)} placeholder="random" />
          </div>
          {mode === 'generate' && (
            <>
              <div className="field">
                <label>Width</label>
                <input value={width} onChange={(e) => setWidth(e.target.value)} />
              </div>
              <div className="field">
                <label>Height</label>
                <input value={height} onChange={(e) => setHeight(e.target.value)} />
              </div>
            </>
          )}
          <div className="field">
            <label>Num images</label>
            <input value={numImages} onChange={(e) => setNumImages(e.target.value)} />
          </div>
        </div>

        <button className="btn btn-primary" disabled={generating} onClick={generate}>
          {generating ? 'Generating…' : 'Generate'}
        </button>
      </div>

      {resultImages.length > 0 && (
        <>
          <div className="page-title" style={{ fontSize: 16 }}>Result</div>
          <div className="thumbnail-grid">
            {resultImages.map((b64, i) => (
              <div key={i} className="card thumbnail-card">
                <img src={`data:image/png;base64,${b64}`} style={{ width: '100%', height: 'auto', borderRadius: 6 }} alt="" />
              </div>
            ))}
          </div>
        </>
      )}
    </>
  );
}

function SystemPage() {
  const [view, setView] = useState('menu'); // 'menu' | 'tables' | 'table-detail' | 'imagegen'
  const [tables, setTables] = useState([]);
  const [detail, setDetail] = useState(null);
  const [error, setError] = useState(null);

  const openTables = () => {
    setError(null);
    Api.system.tables().then(setTables).then(() => setView('tables')).catch((e) => setError(e.message));
  };

  const openTable = (name) => {
    setError(null);
    Api.system.tableDetail(name).then(setDetail).then(() => setView('table-detail')).catch((e) => setError(e.message));
  };

  return (
    <div>
      <div className="page-title">System</div>
      {error && <div className="error-banner">{error}</div>}

      {view === 'menu' && (
        <div className="card">
          <div className="list-row" onClick={openTables}>Tables</div>
          <div className="list-row" onClick={() => setView('imagegen')}>Generate Images Operation</div>
        </div>
      )}

      {view === 'tables' && (
        <>
          <button className="btn" style={{ marginBottom: 12 }} onClick={() => setView('menu')}>← Back</button>
          <div className="card">
            {tables.map((name) => (
              <div key={name} className="list-row mono" onClick={() => openTable(name)}>
                {name}
              </div>
            ))}
            {tables.length === 0 && <div className="empty-state">No tables found.</div>}
          </div>
        </>
      )}

      {view === 'table-detail' && detail && (
        <>
          <button className="btn" style={{ marginBottom: 12 }} onClick={() => setView('tables')}>← Back</button>
          <div className="page-title mono" style={{ fontSize: 16 }}>{detail.name}</div>

          <div className="card">
            <div className="table-scroll">
              <table className="table">
                <thead>
                  <tr><th>Column</th><th>Type</th><th>Nullable</th><th>Default</th></tr>
                </thead>
                <tbody>
                  {detail.columns.map((c) => (
                    <tr key={c.column_name}>
                      <td className="mono">{c.column_name}</td>
                      <td className="mono">{c.data_type}</td>
                      <td>{c.is_nullable}</td>
                      <td className="mono">{c.column_default || '—'}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>

          <div className="page-title" style={{ fontSize: 16 }}>Example row</div>
          <div className="card">
            {detail.example ? (
              <pre className="mono" style={{ margin: 0, whiteSpace: 'pre-wrap', wordBreak: 'break-all', fontSize: 13 }}>
                {JSON.stringify(detail.example, null, 2)}
              </pre>
            ) : (
              <div className="empty-state">Table is empty.</div>
            )}
          </div>
        </>
      )}

      {view === 'imagegen' && (
        <>
          <button className="btn" style={{ marginBottom: 12 }} onClick={() => setView('menu')}>← Back</button>
          <ImageGenPlayground />
        </>
      )}
    </div>
  );
}
