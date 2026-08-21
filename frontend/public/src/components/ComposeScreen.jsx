function timeUntil(target) {
  if (!target) return null;
  const diffMs = new Date(target.replace(' ', 'T')).getTime() - Date.now();
  if (Number.isNaN(diffMs)) return null;
  const past = diffMs < 0;
  const abs = Math.abs(diffMs);
  const hours = Math.floor(abs / 3600000);
  const minutes = Math.floor((abs % 3600000) / 60000);
  const parts = [];
  if (hours) parts.push(`${hours}h`);
  parts.push(`${minutes}m`);
  return (past ? 'overdue by ' : 'in ') + parts.join(' ');
}

function ComposeScreen() {
  const { selectedAccountId } = useAccountContext();
  const [next, setNext] = useState(undefined);
  const [error, setError] = useState(null);
  const [posting, setPosting] = useState(false);
  const [postResult, setPostResult] = useState(null);
  const toast = useToast();

  // Upload form state.
  const [topic, setTopic] = useState('');
  const [file, setFile] = useState(null);
  const [uploading, setUploading] = useState(false);
  const [uploadResult, setUploadResult] = useState(null);

  const loadNext = useCallback(() => {
    if (!selectedAccountId) return;
    Api.compose.next(selectedAccountId).then((res) => setNext(res)).catch((e) => setError(e.message));
  }, [selectedAccountId]);

  useEffect(loadNext, [loadNext]);

  const post = () => {
    setPosting(true);
    setError(null);
    setPostResult(null);
    Api.compose.postNext(selectedAccountId)
      .then((video) => { setPostResult(video); toast.show('Video published'); loadNext(); })
      .catch((e) => setError(e.message))
      .finally(() => setPosting(false));
  };

  const upload = () => {
    if (!file) return;
    setUploading(true);
    setError(null);
    const form = new FormData();
    form.append('account_id', selectedAccountId);
    form.append('topic', topic);
    form.append('video', file);
    Api.videos.upload(form)
      .then((video) => { setUploadResult(video); setTopic(''); setFile(null); toast.show('Draft video created'); })
      .catch((e) => setError(e.message))
      .finally(() => setUploading(false));
  };

  return (
    <div>
      <div className="page-title">Compose</div>
      {error && <div className="error-banner">{error}</div>}

      <div className="card">
        <h3 style={{ marginTop: 0 }}>Upload a new video</h3>
        <div className="field">
          <label>Topic</label>
          <input value={topic} onChange={(e) => setTopic(e.target.value)} placeholder="What's this video about?" />
        </div>
        <div className="field">
          <label>Video file</label>
          <input type="file" accept="video/*" onChange={(e) => setFile(e.target.files[0] || null)} />
        </div>
        <button className="btn btn-primary" disabled={uploading || !file} onClick={upload}>
          {uploading ? 'Uploading…' : 'Upload'}
        </button>
        {uploadResult && <p>Created draft video #{uploadResult.id}. Finish it on the Video Operations page.</p>}
      </div>

      <div className="card">
        <h3 style={{ marginTop: 0 }}>Next up to post</h3>
        {next === undefined && <div className="empty-state">Loading…</div>}
        {next && !next.video && <div className="empty-state">No scheduled video queued for this account.</div>}
        {next && next.video && (
          <div className="compose-preview">
            {next.thumbnail && next.thumbnail.rendered_image && (
              <img loading="lazy" width="320" height="180" src={storageUrl(next.thumbnail.rendered_image)} alt="thumbnail preview" />
            )}
            <div>
              <h4 style={{ marginTop: 0 }}>{next.video.title}</h4>
              <p>{next.video.description}</p>
              <p>
                <StatusPill status={next.video.status} />
                {' — '}
                <span className="mono">{next.video.scheduled_at}</span>
                {' '}
                <span className="countdown">({timeUntil(next.video.scheduled_at)})</span>
              </p>
              <button className="btn btn-primary" disabled={posting} onClick={post}>
                {posting ? 'Posting…' : 'Post now'}
              </button>
            </div>
          </div>
        )}
        {postResult && <p>Video #{postResult.id} is now <StatusPill status={postResult.status} />.</p>}
      </div>
    </div>
  );
}
