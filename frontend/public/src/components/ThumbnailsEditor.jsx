function ThumbnailsEditorPage() {
  const { selectedAccountId } = useAccountContext();
  const [videos, setVideos] = useState([]);
  const [thumbnails, setThumbnails] = useState([]);
  const [presets, setPresets] = useState([]);
  const [videoId, setVideoId] = useState('');
  const [texts, setTexts] = useState({ text_1: '', text_2: '', text_3: '' });
  const [files, setFiles] = useState({});
  const [saving, setSaving] = useState(false);
  const [savingPreset, setSavingPreset] = useState(false);
  const [error, setError] = useState(null);
  const [savedThumbnail, setSavedThumbnail] = useState(null);
  const [deletingId, setDeletingId] = useState(null);
  const [confirmingId, setConfirmingId] = useState(null);
  const [deletingPresetId, setDeletingPresetId] = useState(null);
  const [confirmingPresetId, setConfirmingPresetId] = useState(null);
  const toast = useToast();

  const loadAll = useCallback(() => {
    if (!selectedAccountId) return;
    Api.videos.list(selectedAccountId).then(setVideos).catch((e) => setError(e.message));
    Api.thumbnails.list(selectedAccountId).then(setThumbnails).catch((e) => setError(e.message));
    Api.thumbnailPresets.list(selectedAccountId).then(setPresets).catch((e) => setError(e.message));
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
      .then((thumb) => { setSavedThumbnail(thumb); toast.show('Thumbnail rendered'); loadAll(); })
      .catch((e) => setError(e.message))
      .finally(() => setSaving(false));
  };

  const deleteThumbnail = () => {
    setDeletingId(confirmingId);
    Api.thumbnails.remove(confirmingId)
      .then(() => { toast.show('Thumbnail deleted'); loadAll(); })
      .catch((e) => setError(e.message))
      .finally(() => { setDeletingId(null); setConfirmingId(null); });
  };

  const saveAsPreset = () => {
    setSavingPreset(true);
    setError(null);
    const form = new FormData();
    form.append('channel_id', selectedAccountId);
    Object.entries(texts).forEach(([k, v]) => form.append(k, v));
    Object.entries(files).forEach(([k, f]) => { if (f) form.append(k, f); });

    Api.thumbnailPresets.save(form)
      .then((preset) => { toast.show(`Preset ${preset.reference_no} saved`); loadAll(); })
      .catch((e) => setError(e.message))
      .finally(() => setSavingPreset(false));
  };

  const deletePreset = () => {
    setDeletingPresetId(confirmingPresetId);
    Api.thumbnailPresets.remove(confirmingPresetId)
      .then(() => { toast.show('Preset deleted'); loadAll(); })
      .catch((e) => setError(e.message))
      .finally(() => { setDeletingPresetId(null); setConfirmingPresetId(null); });
  };

  const imageSlots = ['background_image', 'image_1', 'image_2', 'image_3', 'image_4', 'image_5'];

  return (
    <div>
      <div className="page-title">Thumbnails</div>
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
        <button className="btn" disabled={savingPreset} onClick={saveAsPreset} style={{ marginLeft: 8 }}>
          {savingPreset ? 'Saving…' : 'Save as Preset'}
        </button>

        {savedThumbnail && savedThumbnail.rendered_image && (
          <div style={{ marginTop: 16 }}>
            <img loading="lazy" width="480" height="270" src={storageUrl(savedThumbnail.rendered_image)} style={{ maxWidth: 480, width: '100%', height: 'auto', borderRadius: 8 }} alt="rendered thumbnail" />
          </div>
        )}
      </div>

      <div className="page-title">Existing thumbnails</div>
      <div className="thumbnail-grid">
        {thumbnails.map((t) => (
          <div key={t.id} className="card thumbnail-card">
            <button
              className="thumbnail-card-delete"
              aria-label={`Delete thumbnail for video #${t.video_id}`}
              title="Delete thumbnail"
              onClick={() => setConfirmingId(t.id)}
            >
              ✕
            </button>
            {t.rendered_image && <img loading="lazy" width="220" height="124" src={storageUrl(t.rendered_image)} style={{ width: '100%', height: 'auto', borderRadius: 6 }} alt="thumbnail" />}
            <div className="mono" style={{ fontSize: 12, marginTop: 6 }}>Video #{t.video_id}</div>
          </div>
        ))}
        {thumbnails.length === 0 && <div className="empty-state">No thumbnails yet.</div>}
      </div>

      <ConfirmDialog
        open={confirmingId !== null}
        title="Delete this thumbnail?"
        message="This removes the thumbnail composition and its rendered image. This can't be undone."
        busy={deletingId !== null}
        onConfirm={deleteThumbnail}
        onCancel={() => setConfirmingId(null)}
      />

      <div className="page-title">Thumbnail Presets</div>
      <div className="thumbnail-grid">
        {presets.map((p) => (
          <div key={p.id} className="card thumbnail-card">
            <button
              className="thumbnail-card-delete"
              aria-label={`Delete preset ${p.reference_no}`}
              title="Delete preset"
              onClick={() => setConfirmingPresetId(p.id)}
            >
              ✕
            </button>
            {p.rendered_image && <img loading="lazy" width="220" height="124" src={storageUrl(p.rendered_image)} style={{ width: '100%', height: 'auto', borderRadius: 6 }} alt="thumbnail preset" />}
            <div className="mono" style={{ fontSize: 12, marginTop: 6 }}>{p.reference_no}</div>
          </div>
        ))}
        {presets.length === 0 && <div className="empty-state">No thumbnail presets yet.</div>}
      </div>

      <ConfirmDialog
        open={confirmingPresetId !== null}
        title="Delete this preset?"
        message="This removes the preset composition and its rendered image. This can't be undone."
        busy={deletingPresetId !== null}
        onConfirm={deletePreset}
        onCancel={() => setConfirmingPresetId(null)}
      />
    </div>
  );
}
