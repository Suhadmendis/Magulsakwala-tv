function capitalize(s) {
  return s ? s.charAt(0).toUpperCase() + s.slice(1) : s;
}

function wordCount(text) {
  return text && text.trim() ? text.trim().split(/\s+/).length : 0;
}

function VideoOperationsPage() {
  const { selectedAccountId } = useAccountContext();
  const [error, setError] = useState(null);
  const [pickerOpen, setPickerOpen] = useState(false);
  const [selectedTopic, setSelectedTopic] = useState(null);
  const [zodiacVideos, setZodiacVideos] = useState([]);
  const [contentByZodiacVideo, setContentByZodiacVideo] = useState({});
  const [thumbById, setThumbById] = useState({});
  const [creatingId, setCreatingId] = useState(null);
  const [creatingPhase, setCreatingPhase] = useState(null);
  const [deletingId, setDeletingId] = useState(null);
  const [runningAll, setRunningAll] = useState(false);
  const [runProgress, setRunProgress] = useState(null);
  const [deletingAll, setDeletingAll] = useState(false);
  const [deleteProgress, setDeleteProgress] = useState(null);
  const [confirmDeleteAllOpen, setConfirmDeleteAllOpen] = useState(false);
  const toast = useToast();

  const load = useCallback(() => {
    if (!selectedAccountId) return;
    Api.zodiacVideos.list(selectedAccountId).then(setZodiacVideos).catch((e) => setError(e.message));
    Api.contentFeeder.list(selectedAccountId)
      .then((entries) => {
        const map = {};
        entries.forEach((e) => {
          if (e.zodiac_video_ref && !map[e.zodiac_video_ref]) map[e.zodiac_video_ref] = e.content;
        });
        setContentByZodiacVideo(map);
      })
      .catch(() => {});
    Api.thumbnailPresets.list(selectedAccountId)
      .then((list) => {
        const map = {};
        list.forEach((t) => { map[t.id] = t; });
        setThumbById(map);
      })
      .catch(() => {});
  }, [selectedAccountId]);

  useEffect(load, [load]);

  const createAudioAndVideo = (zv) => {
    setCreatingId(zv.id);
    setCreatingPhase('audio');
    setError(null);
    Api.zodiacVideos.generateVoiceOver(zv.id)
      .then(() => { setCreatingPhase('thumbnail'); return Api.zodiacVideos.generateThumbnail(zv.id); })
      .then(() => { setCreatingPhase('video'); return Api.zodiacVideos.render(zv.id); })
      .then(() => { toast.show(`${capitalize(zv.zodiac_sign)} audio + video created`); load(); })
      .catch((e) => setError(e.message))
      .finally(() => { setCreatingId(null); setCreatingPhase(null); });
  };

  const deleteMedia = (zv) => {
    setDeletingId(zv.id);
    setError(null);
    Api.zodiacVideos.deleteMedia(zv.id)
      .then(() => { toast.show(`${capitalize(zv.zodiac_sign)} audio + video deleted`); load(); })
      .catch((e) => setError(e.message))
      .finally(() => setDeletingId(null));
  };

  const createAll = () => {
    const queue = zodiacVideos.filter((zv) => !(zv.voice_over_url && zv.rendered_video_url));
    if (queue.length === 0) {
      toast.show('Every sign already has audio + video');
      return;
    }
    setError(null);
    setRunningAll(true);

    const step = (i) => {
      if (i >= queue.length) {
        setRunningAll(false);
        setRunProgress(null);
        setCreatingId(null);
        toast.show('Create All finished');
        load();
        return;
      }
      const zv = queue[i];
      setCreatingId(zv.id);
      setCreatingPhase('audio');
      setRunProgress({ index: i + 1, total: queue.length, sign: capitalize(zv.zodiac_sign) });
      Api.zodiacVideos.generateVoiceOver(zv.id)
        .then(() => { setCreatingPhase('thumbnail'); return Api.zodiacVideos.generateThumbnail(zv.id); })
        .then(() => { setCreatingPhase('video'); return Api.zodiacVideos.render(zv.id); })
        .then(() => step(i + 1))
        .catch((e) => {
          setError(`${capitalize(zv.zodiac_sign)}: ${e.message}`);
          setRunningAll(false);
          setRunProgress(null);
          setCreatingId(null);
          setCreatingPhase(null);
          load();
        });
    };
    step(0);
  };

  const deleteAll = () => {
    setConfirmDeleteAllOpen(false);
    const queue = zodiacVideos.filter((zv) => zv.voice_over_url || zv.rendered_video_url || zv.thumbnail_preset_ref);
    if (queue.length === 0) {
      toast.show('Nothing to delete');
      return;
    }
    setError(null);
    setDeletingAll(true);

    const step = (i) => {
      if (i >= queue.length) {
        setDeletingAll(false);
        setDeleteProgress(null);
        setDeletingId(null);
        toast.show('Delete All finished');
        load();
        return;
      }
      const zv = queue[i];
      setDeletingId(zv.id);
      setDeleteProgress({ index: i + 1, total: queue.length, sign: capitalize(zv.zodiac_sign) });
      Promise.all([
        (zv.voice_over_url || zv.rendered_video_url) ? Api.zodiacVideos.deleteMedia(zv.id) : Promise.resolve(),
        zv.thumbnail_preset_ref ? Api.zodiacVideos.deleteThumbnail(zv.id) : Promise.resolve(),
      ])
        .then(() => step(i + 1))
        .catch((e) => {
          setError(`${capitalize(zv.zodiac_sign)}: ${e.message}`);
          setDeletingAll(false);
          setDeleteProgress(null);
          setDeletingId(null);
          load();
        });
    };
    step(0);
  };

  return (
    <div>
      <div className="page-title">Video Operations</div>
      {error && <div className="error-banner">{error}</div>}

      <div className="card" style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
        <div>
          <div className="eyebrow" style={{ marginBottom: 4 }}>Selected topic</div>
          <div>{selectedTopic ? (selectedTopic.topic || selectedTopic.title) : 'None selected'}</div>
        </div>
        <button className="btn" onClick={() => setPickerOpen(true)}>
          {selectedTopic ? 'Change topic' : 'Select topic'}
        </button>
      </div>

      {selectedTopic && (
        <>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginTop: 24 }}>
            <div style={{ flex: 1 }}>
              <div className="page-title" style={{ fontSize: 16 }}>Zodiac Sign Videos</div>
              <p className="page-subtitle">
                {runningAll && runProgress
                  ? `Creating ${runProgress.sign} (${runProgress.index}/${runProgress.total})…`
                  : deletingAll && deleteProgress
                  ? `Deleting ${deleteProgress.sign} (${deleteProgress.index}/${deleteProgress.total})…`
                  : 'The 12 zodiac sign video slots for this channel.'}
              </p>
              {runningAll && runProgress && (
                <div className="progress-track">
                  <div className="progress-fill" style={{ width: `${(runProgress.index / runProgress.total) * 100}%` }} />
                </div>
              )}
              {deletingAll && deleteProgress && (
                <div className="progress-track">
                  <div className="progress-fill progress-fill-danger" style={{ width: `${(deleteProgress.index / deleteProgress.total) * 100}%` }} />
                </div>
              )}
            </div>
            <div style={{ display: 'flex', gap: 8 }}>
              <button className="btn btn-primary" disabled={runningAll || deletingAll} onClick={createAll}>
                {runningAll ? 'Creating…' : 'Create All'}
              </button>
              <button className="btn btn-danger" disabled={deletingAll || runningAll} onClick={() => setConfirmDeleteAllOpen(true)}>
                {deletingAll ? 'Deleting…' : 'Delete All'}
              </button>
            </div>
          </div>

          <ConfirmDialog
            open={confirmDeleteAllOpen}
            title="Delete all thumbnails, audio and video?"
            message="This removes the generated thumbnail, voice-over, and rendered video for every sign in this list. Fed content and titles are kept. This can't be undone."
            busy={deletingAll}
            onConfirm={deleteAll}
            onCancel={() => setConfirmDeleteAllOpen(false)}
          />
          <AriesPreview zodiacVideos={zodiacVideos} thumbById={thumbById} />

          <div className="card">
            <div className="table-scroll">
              <table className="table">
                <thead>
                  <tr>
                    <th>Sign</th><th>Thumbnail</th><th>Title</th><th>Word Count</th>
                    <th>Type</th><th>Status</th><th>Audio</th><th>Video</th><th></th>
                  </tr>
                </thead>
                <tbody>
                  {zodiacVideos.map((zv) => {
                    const thumb = zv.thumbnail_preset_ref ? thumbById[zv.thumbnail_preset_ref] : null;
                    const content = contentByZodiacVideo[zv.id];
                    return (
                      <tr key={zv.id} style={{ height: 130 }}>
                        <td>{capitalize(zv.zodiac_sign)}</td>
                        <td>
                          {creatingId === zv.id && creatingPhase === 'thumbnail' ? (
                            <span><span className="spinner" />Generating…</span>
                          ) : thumb && thumb.rendered_image ? (
                            <img src={storageUrl(thumb.rendered_image)} alt="" width="120" height="68" style={{ objectFit: 'cover', borderRadius: 4 }} />
                          ) : (
                            <div className="table-thumb-placeholder" style={{ width: 120, height: 68 }} />
                          )}
                        </td>
                        <td>{zv.title || '(untitled)'}</td>
                        <td>{content ? `${wordCount(content)} words` : '—'}</td>
                        <td>{zv.video_type || '—'}</td>
                        <td><StatusPill status={zv.status} /></td>
                        <td>
                          {creatingId === zv.id && creatingPhase === 'audio' ? (
                            <span><span className="spinner" />Generating…</span>
                          ) : zv.voice_over_url ? (
                            <audio controls preload="none" src={zv.voice_over_url} style={{ height: 32, width: 160 }} />
                          ) : 'Not ready'}
                        </td>
                        <td>
                          {creatingId === zv.id && creatingPhase === 'video' ? (
                            <span><span className="spinner" />Rendering…</span>
                          ) : zv.rendered_video_url ? (
                            <video
                              controls preload="none" width="120" height="68"
                              poster={thumb && thumb.rendered_image ? storageUrl(thumb.rendered_image) : undefined}
                              src={zv.rendered_video_url}
                              style={{ objectFit: 'cover', borderRadius: 4 }}
                            />
                          ) : 'Not ready'}
                        </td>
                        <td>
                          <div style={{ display: 'flex', gap: 6 }}>
                            <button className="btn" onClick={() => createAudioAndVideo(zv)}>
                              {creatingId === zv.id ? 'Creating…' : 'Create'}
                            </button>
                            <button
                              className="btn btn-danger"
                              disabled={!zv.voice_over_url && !zv.rendered_video_url}
                              onClick={() => deleteMedia(zv)}
                            >
                              {deletingId === zv.id ? 'Deleting…' : 'Delete'}
                            </button>
                          </div>
                        </td>
                      </tr>
                    );
                  })}
                  {zodiacVideos.length === 0 && (
                    <tr><td colSpan="9" className="empty-state">No zodiac videos yet — created automatically when the channel was added.</td></tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </>
      )}

      {pickerOpen && (
        <TopicPickerDialog
          accountId={selectedAccountId}
          onClose={() => setPickerOpen(false)}
          onSelect={(video) => { setPickerOpen(false); setSelectedTopic(video); }}
        />
      )}
    </div>
  );
}

function AriesPreview({ zodiacVideos, thumbById }) {
  const aries = zodiacVideos.find((zv) => zv.zodiac_sign === 'aries');
  const thumb = aries && aries.thumbnail_preset_ref ? thumbById[aries.thumbnail_preset_ref] : null;

  return (
    <div className="card" style={{ display: 'flex', gap: 16, flexWrap: 'wrap' }}>
      <div style={{ flex: 1, minWidth: 280 }}>
        <div className="eyebrow" style={{ marginBottom: 8 }}>Thumbnail (Aries example)</div>
        {thumb && thumb.rendered_image ? (
          <img
            src={storageUrl(thumb.rendered_image)}
            alt="Aries thumbnail"
            style={{ width: '100%', aspectRatio: '16 / 9', objectFit: 'cover', borderRadius: 8 }}
          />
        ) : (
          <div className="table-thumb-placeholder" style={{ width: '100%', aspectRatio: '16 / 9', borderRadius: 8 }} />
        )}
      </div>

      <div style={{ flex: 1, minWidth: 280 }}>
        <div className="eyebrow" style={{ marginBottom: 8 }}>Video (Aries example)</div>
        {aries && aries.rendered_video_url ? (
          <video
            controls
            src={aries.rendered_video_url}
            poster={thumb && thumb.rendered_image ? storageUrl(thumb.rendered_image) : undefined}
            style={{ width: '100%', aspectRatio: '16 / 9', borderRadius: 8, background: '#111' }}
          />
        ) : (
          <div style={{ width: '100%', aspectRatio: '16 / 9', borderRadius: 8, background: '#111' }} />
        )}
      </div>
    </div>
  );
}

function TopicPickerDialog({ accountId, onClose, onSelect }) {
  const [q, setQ] = useState('');
  const [debouncedQ, setDebouncedQ] = useState('');
  const [results, setResults] = useState([]);
  const [error, setError] = useState(null);

  useEffect(() => {
    const timer = setTimeout(() => setDebouncedQ(q), 300);
    return () => clearTimeout(timer);
  }, [q]);

  useEffect(() => {
    Api.videos.search(accountId, { q: debouncedQ || undefined })
      .then((rows) => setResults(rows.filter((v) => v.topic)))
      .catch((e) => setError(e.message));
  }, [accountId, debouncedQ]);

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="modal" onClick={(e) => e.stopPropagation()}>
        <div className="page-title">Select Topic</div>
        {error && <div className="error-banner">{error}</div>}
        <input
          autoFocus
          placeholder="Search topics…"
          value={q}
          onChange={(e) => setQ(e.target.value)}
          style={{ width: '100%', padding: 8, marginBottom: 12 }}
        />
        <div className="table-scroll">
          <table className="table">
            <tbody>
              {results.map((v) => (
                <tr key={v.id} style={{ cursor: 'pointer' }} onClick={() => onSelect(v)}>
                  <td>{v.topic}</td>
                  <td><StatusPill status={v.status} /></td>
                </tr>
              ))}
              {results.length === 0 && <tr><td className="empty-state">No fed topics match.</td></tr>}
            </tbody>
          </table>
        </div>
        <div style={{ marginTop: 12 }}>
          <button className="btn" onClick={onClose}>Close</button>
        </div>
      </div>
    </div>
  );
}
