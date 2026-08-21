const MASTER_PROMPT = `This is a female talking to the viewers — speak directly to the audience, one on one.

Talking style: very casual, conversational, like talking to a friend. Nothing stiff or scripted-sounding.

Psychologically hook the viewer and keep them engaged from the first line to the last.

Topic: Western astrology. Keep the curiosity of the audience very high throughout — tease what's coming, don't give everything away up front.`;

function ContentFeederPage() {
  const { selectedAccountId } = useAccountContext();
  const [error, setError] = useState(null);
  const [pickerOpen, setPickerOpen] = useState(false);
  const [masterPromptOpen, setMasterPromptOpen] = useState(false);
  const [selectedTopic, setSelectedTopic] = useState(null);
  const [zodiacVideos, setZodiacVideos] = useState([]);
  const [entryByZodiacVideo, setEntryByZodiacVideo] = useState({});

  const load = useCallback(() => {
    if (!selectedAccountId) return;
    Api.zodiacVideos.list(selectedAccountId).then(setZodiacVideos).catch((e) => setError(e.message));
    Api.contentFeeder.list(selectedAccountId)
      .then((entries) => {
        const map = {};
        entries.forEach((e) => {
          if (e.zodiac_video_ref && !map[e.zodiac_video_ref]) map[e.zodiac_video_ref] = e;
        });
        setEntryByZodiacVideo(map);
      })
      .catch(() => {});
  }, [selectedAccountId]);

  useEffect(load, [load]);

  return (
    <div>
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
        <div className="page-title">Content Feeder</div>
        <button className="btn" onClick={() => setMasterPromptOpen(true)}>Master Prompt</button>
      </div>
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

      {masterPromptOpen && <MasterPromptDialog onClose={() => setMasterPromptOpen(false)} />}

      {selectedTopic && (
        <>
          <div className="page-title" style={{ fontSize: 16, marginTop: 24 }}>Zodiac Sign Videos</div>
          <p className="page-subtitle">The 12 zodiac sign video slots for this channel.</p>

          {zodiacVideos.map((zv) => (
            <ZodiacContentPanel key={zv.id} video={zv} entry={entryByZodiacVideo[zv.id]} />
          ))}

          {zodiacVideos.length === 0 && (
            <div className="card empty-state">No zodiac videos yet — created automatically when the channel was added.</div>
          )}
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

function MasterPromptDialog({ onClose }) {
  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="modal modal-small" onClick={(e) => e.stopPropagation()}>
        <div className="page-title">Master Prompt</div>
        <p style={{ whiteSpace: 'pre-wrap', marginTop: 12 }}>{MASTER_PROMPT}</p>
        <div style={{ marginTop: 12 }}>
          <button className="btn" onClick={onClose}>Close</button>
        </div>
      </div>
    </div>
  );
}

function ZodiacContentPanel({ video, entry }) {
  const [hook, setHook] = useState(entry ? entry.hook || '' : '');
  const [content, setContent] = useState(entry ? entry.content || '' : '');
  const [cta, setCta] = useState(entry ? entry.cta || '' : '');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState(null);
  const toast = useToast();

  useEffect(() => {
    setHook(entry ? entry.hook || '' : '');
    setContent(entry ? entry.content || '' : '');
    setCta(entry ? entry.cta || '' : '');
  }, [entry]);

  const save = () => {
    if (!entry) return;
    setSaving(true);
    setError(null);
    Api.contentFeeder.update(entry.id, { hook, content, cta })
      .then(() => toast.show(`${capitalize(video.zodiac_sign)} content saved`))
      .catch((e) => setError(e.message))
      .finally(() => setSaving(false));
  };

  return (
    <div className="card" style={{ marginBottom: 16 }}>
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
        <div style={{ fontWeight: 600 }}>{capitalize(video.zodiac_sign)}</div>
        <div>{video.title || '(untitled)'}</div>
        <div>{video.video_type || '—'}</div>
        <StatusPill status={video.status} />
      </div>

      {error && <div className="error-banner">{error}</div>}

      {entry ? (
        <>
          <div className="field" style={{ marginTop: 12 }}>
            <label>Hook</label>
            <input value={hook} onChange={(e) => setHook(e.target.value)} style={{ width: '100%' }} />
          </div>

          <div className="field">
            <label>Content ({wordCount(content)} words)</label>
            <textarea rows="10" value={content} onChange={(e) => setContent(e.target.value)} style={{ width: '100%' }} />
          </div>

          <div className="field">
            <label>CTA</label>
            <input value={cta} onChange={(e) => setCta(e.target.value)} style={{ width: '100%' }} />
          </div>

          <div style={{ display: 'flex', justifyContent: 'flex-end' }}>
            <button
              className="btn btn-primary"
              style={{ padding: '2px 10px', fontSize: 12 }}
              disabled={saving}
              onClick={save}
            >
              {saving ? 'Saving…' : 'Save'}
            </button>
          </div>
        </>
      ) : (
        <div className="field" style={{ marginTop: 12 }}>
          <span>No content fed yet.</span>
        </div>
      )}
    </div>
  );
}
