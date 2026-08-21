function FindingsPage() {
  const { selectedAccountId } = useAccountContext();
  const [raw, setRaw] = useState('{\n  "elements": [\n    { "topic": "" }\n  ]\n}');
  const [error, setError] = useState(null);
  const [result, setResult] = useState(null);
  const [posting, setPosting] = useState(false);
  const toast = useToast();

  const post = () => {
    setError(null);
    setResult(null);
    let parsed;
    try {
      parsed = JSON.parse(raw);
    } catch (e) {
      setError('Invalid JSON: ' + e.message);
      return;
    }
    if (!Array.isArray(parsed.elements)) {
      setError('JSON must have an "elements" array.');
      return;
    }

    setPosting(true);
    Api.findings.import(selectedAccountId, parsed.elements)
      .then((res) => { setResult(res); toast.show(`Created ${res.count} draft video(s)`); })
      .catch((e) => setError(e.message))
      .finally(() => setPosting(false));
  };

  return (
    <div>
      <div className="page-title">Findings</div>
      <p>Paste a JSON payload of topics — each element becomes a new draft video for the selected account.</p>
      {error && <div className="error-banner">{error}</div>}
      {result && <div className="card">Created {result.count} draft video(s).</div>}

      <div className="card">
        <textarea
          rows="14"
          style={{ width: '100%', fontFamily: 'monospace', fontSize: 13 }}
          value={raw}
          onChange={(e) => setRaw(e.target.value)}
        />
        <div style={{ marginTop: 12 }}>
          <button className="btn btn-primary" disabled={posting} onClick={post}>
            {posting ? 'Posting…' : 'Post'}
          </button>
        </div>
      </div>
    </div>
  );
}
