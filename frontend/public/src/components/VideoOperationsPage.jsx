function statusPillClass(status) {
  return 'status-pill status-' + status;
}

function VideoOperationsPage() {
  const { selectedAccountId } = useAccountContext();
  const [videos, setVideos] = useState([]);
  const [selectedVideo, setSelectedVideo] = useState(null);
  const [searchOpen, setSearchOpen] = useState(false);
  const [error, setError] = useState(null);

  const load = useCallback(() => {
    if (!selectedAccountId) return;
    Api.videos.list(selectedAccountId).then(setVideos).catch((e) => setError(e.message));
  }, [selectedAccountId]);

  useEffect(load, [load]);

  const openVideo = (id) => {
    Api.videos.get(id).then(setSelectedVideo).catch((e) => setError(e.message));
  };

  return (
    <div>
      <div className="page-title">Video Operations</div>
      {error && <div className="error-banner">{error}</div>}

      <div style={{ marginBottom: 12, display: 'flex', gap: 8 }}>
        <button className="btn" onClick={() => setSearchOpen(true)}>Search</button>
      </div>

      <div className="card">
        <table className="table">
          <thead>
            <tr><th>Title / Topic</th><th>Type</th><th>Status</th><th>Scheduled</th><th></th></tr>
          </thead>
          <tbody>
            {videos.map((v) => (
              <tr key={v.id}>
                <td>{v.title || v.topic || '(untitled)'}</td>
                <td>{v.video_type || '—'}</td>
                <td><span className={statusPillClass(v.status)}>{v.status}</span></td>
                <td>{v.scheduled_at || '—'}</td>
                <td><button className="btn" onClick={() => openVideo(v.id)}>Edit</button></td>
              </tr>
            ))}
            {videos.length === 0 && (
              <tr><td colSpan="5" className="empty-state">No videos yet. Use Findings or the Compose screen to create one.</td></tr>
            )}
          </tbody>
        </table>
      </div>

      {searchOpen && (
        <VideoSearchDialog
          accountId={selectedAccountId}
          onClose={() => setSearchOpen(false)}
          onSelect={(video) => { setSearchOpen(false); setSelectedVideo(video); }}
        />
      )}

      {selectedVideo && (
        <VideoEditForm
          video={selectedVideo}
          onClose={() => { setSelectedVideo(null); load(); }}
        />
      )}
    </div>
  );
}

function VideoSearchDialog({ accountId, onClose, onSelect }) {
  const [q, setQ] = useState('');
  const [videoType, setVideoType] = useState('');
  const [results, setResults] = useState([]);
  const [error, setError] = useState(null);

  const runSearch = useCallback(() => {
    Api.videos.search(accountId, { q: q || undefined, video_type: videoType || undefined })
      .then(setResults)
      .catch((e) => setError(e.message));
  }, [accountId, q, videoType]);

  useEffect(runSearch, [runSearch]);

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="modal" onClick={(e) => e.stopPropagation()}>
        <div className="page-title">Search Videos</div>
        {error && <div className="error-banner">{error}</div>}
        <div style={{ display: 'flex', gap: 8, marginBottom: 12 }}>
          <input placeholder="Search title/topic…" value={q} onChange={(e) => setQ(e.target.value)} style={{ flex: 1, padding: 8 }} />
          <select value={videoType} onChange={(e) => setVideoType(e.target.value)}>
            <option value="">Any type</option>
            <option value="long">Long</option>
            <option value="short">Short</option>
          </select>
          <button className="btn" onClick={runSearch}>Search</button>
        </div>
        <table className="table">
          <tbody>
            {results.map((v) => (
              <tr key={v.id} style={{ cursor: 'pointer' }} onClick={() => onSelect(v)}>
                <td>{v.title || v.topic || '(untitled)'}</td>
                <td><span className={statusPillClass(v.status)}>{v.status}</span></td>
              </tr>
            ))}
            {results.length === 0 && <tr><td className="empty-state">No matching videos (drafts/prepared/scheduled only).</td></tr>}
          </tbody>
        </table>
        <div style={{ marginTop: 12 }}>
          <button className="btn" onClick={onClose}>Close</button>
        </div>
      </div>
    </div>
  );
}

function VideoEditForm({ video: initialVideo, onClose }) {
  const [video, setVideo] = useState(initialVideo);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState(null);
  const [generating, setGenerating] = useState(null);
  const [scheduledAt, setScheduledAt] = useState(initialVideo.scheduled_at || '');

  const update = (field) => (e) => setVideo((v) => ({ ...v, [field]: e.target.value }));

  const save = () => {
    setSaving(true);
    setError(null);
    Api.videos.update(video.id, {
      title: video.title, description: video.description, tags: video.tags,
      topic: video.topic, content: video.content, video_type: video.video_type,
      voice_enabled: video.voice_enabled ? 1 : 0,
    })
      .then(setVideo)
      .catch((e) => setError(e.message))
      .finally(() => setSaving(false));
  };

  const generate = (field, apiCall, resultKey) => {
    setGenerating(field);
    setError(null);
    apiCall(video.id)
      .then((res) => setVideo((v) => ({ ...v, [field]: res[resultKey] })))
      .catch((e) => setError(e.message))
      .finally(() => setGenerating(null));
  };

  const schedule = () => {
    if (!scheduledAt) return;
    Api.videos.schedule(video.id, scheduledAt).then(setVideo).catch((e) => setError(e.message));
  };

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="modal" onClick={(e) => e.stopPropagation()}>
        <div className="page-title">
          Edit Video <span className={statusPillClass(video.status)}>{video.status}</span>
        </div>
        {error && <div className="error-banner">{error}</div>}

        <div className="field">
          <label>Topic</label>
          <input value={video.topic || ''} onChange={update('topic')} />
        </div>

        <div className="field">
          <label>Title {' '}
            <button className="btn" disabled={generating === 'title'} onClick={() => generate('title', Api.content.generateTitle, 'title')}>
              {generating === 'title' ? 'Generating…' : 'Generate (AI)'}
            </button>
          </label>
          <input value={video.title || ''} onChange={update('title')} />
        </div>

        <div className="field">
          <label>Description {' '}
            <button className="btn" disabled={generating === 'description'} onClick={() => generate('description', Api.content.generateDescription, 'description')}>
              {generating === 'description' ? 'Generating…' : 'Generate (AI)'}
            </button>
          </label>
          <textarea rows="3" value={video.description || ''} onChange={update('description')} />
        </div>

        <div className="field">
          <label>Tags {' '}
            <button className="btn" disabled={generating === 'tags'} onClick={() => generate('tags', Api.content.generateTags, 'tags')}>
              {generating === 'tags' ? 'Generating…' : 'Generate (AI)'}
            </button>
          </label>
          <input value={video.tags || ''} onChange={update('tags')} />
        </div>

        <div className="field">
          <label>Content / script {' '}
            <button className="btn" disabled={generating === 'content'} onClick={() => generate('content', Api.content.generateContent, 'content')}>
              {generating === 'content' ? 'Generating…' : 'Generate (AI)'}
            </button>
          </label>
          <textarea rows="4" value={video.content || ''} onChange={update('content')} />
        </div>

        <div className="field" style={{ flexDirection: 'row', alignItems: 'center' }}>
          <input
            type="checkbox"
            id="voice_enabled"
            checked={!!video.voice_enabled}
            onChange={(e) => setVideo((v) => ({ ...v, voice_enabled: e.target.checked ? 1 : 0 }))}
          />
          <label htmlFor="voice_enabled" style={{ marginLeft: 8 }}>Voice-over enabled</label>
          {video.voice_enabled ? (
            <button
              className="btn"
              style={{ marginLeft: 12 }}
              onClick={() => generate('voice_over_path', Api.content.generateVoiceOver, 'voice_over_path')}
            >
              Generate voice-over (Gemini)
            </button>
          ) : null}
        </div>

        <div className="field">
          <label>Video type</label>
          <select value={video.video_type || ''} onChange={update('video_type')}>
            <option value="">Select…</option>
            <option value="long">Long</option>
            <option value="short">Short</option>
          </select>
        </div>

        <div className="field">
          <label>Thumbnail</label>
          {video.thumbnail_ref ? (
            <span>Thumbnail attached (ref #{video.thumbnail_ref})</span>
          ) : (
            <span>No thumbnail yet — create one on the Thumbnails page for this video.</span>
          )}
        </div>

        <div style={{ display: 'flex', gap: 8, marginBottom: 16 }}>
          <button className="btn btn-primary" disabled={saving} onClick={save}>
            {saving ? 'Saving…' : 'Save'}
          </button>
          <button className="btn" onClick={onClose}>Close</button>
        </div>

        {video.status === 'prepared' || video.status === 'scheduled' ? (
          <div className="field" style={{ borderTop: '1px solid #eee', paddingTop: 12 }}>
            <label>Posting date &amp; time</label>
            <input type="datetime-local" value={(scheduledAt || '').replace(' ', 'T').slice(0, 16)}
                   onChange={(e) => setScheduledAt(e.target.value.replace('T', ' ') + ':00')} />
            <button className="btn" style={{ marginTop: 8 }} onClick={schedule}>Set schedule</button>
          </div>
        ) : null}
      </div>
    </div>
  );
}
