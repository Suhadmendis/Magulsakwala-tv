function ThumbnailsEditorPage() {
  const { selectedAccountId } = useAccountContext();
  const [videos, setVideos] = useState([]);
  const [thumbnails, setThumbnails] = useState([]);
  const [videoId, setVideoId] = useState('');
  const [texts, setTexts] = useState({ text_1: '', text_2: '', text_3: '' });
  const [files, setFiles] = useState({});
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState(null);
  const [savedThumbnail, setSavedThumbnail] = useState(null);

  const loadAll = useCallback(() => {
    if (!selectedAccountId) return;
    Api.videos.list(selectedAccountId).then(setVideos).catch((e) => setError(e.message));
    Api.thumbnails.list(selectedAccountId).then(setThumbnails).catch((e) => setError(e.message));
  }, [selectedAccountId]);

  useEffect(loadAll, [loadAll]);

  const setFile = (slot) => (e) => setFiles((f) => ({ ...f, [slot]: e.target.files[0] || null }));

  const save = () => {
    if (!videoId) {
      setError('Pick a video first.');
      return;
    }
    setSaving(true);
    setError(null);
    const form = new FormData();
    form.append('video_id', videoId);
    form.append('account_id', selectedAccountId);
    Object.entries(texts).forEach(([k, v]) => form.append(k, v));
    Object.entries(files).forEach(([k, f]) => { if (f) form.append(k, f); });

    Api.thumbnails.save(form)
      .then((thumb) => { setSavedThumbnail(thumb); loadAll(); })
      .catch((e) => setError(e.message))
      .finally(() => setSaving(false));
  };

  const imageSlots = ['background_image', 'image_1', 'image_2', 'image_3', 'image_4', 'image_5'];

  return (
    <div>
      <div className="page-title">Thumbnails Editor</div>
      {error && <div className="error-banner">{error}</div>}

      <div className="card">
        <div className="field">
          <label>Video</label>
          <select value={videoId} onChange={(e) => setVideoId(e.target.value)}>
            <option value="">Select a video…</option>
            {videos.map((v) => (
              <option key={v.id} value={v.id}>{v.title || v.topic || `Video #${v.id}`}</option>
            ))}
          </select>
        </div>

        <div className="thumbnail-slots">
          {imageSlots.map((slot) => (
            <div className="field" key={slot}>
              <label>{slot.replace('_', ' ')}</label>
              <input type="file" accept="image/*" onChange={setFile(slot)} />
            </div>
          ))}
        </div>

        {['text_1', 'text_2', 'text_3'].map((slot) => (
          <div className="field" key={slot}>
            <label>{slot.replace('_', ' ')}</label>
            <input value={texts[slot]} onChange={(e) => setTexts((t) => ({ ...t, [slot]: e.target.value }))} />
          </div>
        ))}

        <button className="btn btn-primary" disabled={saving} onClick={save}>
          {saving ? 'Rendering…' : 'Save & Render'}
        </button>

        {savedThumbnail && savedThumbnail.rendered_image && (
          <div style={{ marginTop: 16 }}>
            <img src={storageUrl(savedThumbnail.rendered_image)} style={{ maxWidth: 480, borderRadius: 8 }} alt="rendered thumbnail" />
          </div>
        )}
      </div>

      <div className="page-title">Existing thumbnails</div>
      <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap' }}>
        {thumbnails.map((t) => (
          <div key={t.id} className="card" style={{ width: 220 }}>
            {t.rendered_image && <img src={storageUrl(t.rendered_image)} style={{ width: '100%', borderRadius: 6 }} alt="thumbnail" />}
            <div style={{ fontSize: 12, marginTop: 6 }}>Video #{t.video_id}</div>
          </div>
        ))}
        {thumbnails.length === 0 && <div className="empty-state">No thumbnails yet.</div>}
      </div>
    </div>
  );
}
