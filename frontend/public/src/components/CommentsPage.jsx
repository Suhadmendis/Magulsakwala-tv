function CommentsPage() {
  const { selectedAccountId } = useAccountContext();
  const [comments, setComments] = useState([]);
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(true);
  const [drafts, setDrafts] = useState({}); // youtube_comment_id -> reply text
  const [busy, setBusy] = useState(null);
  const toast = useToast();

  const load = useCallback(() => {
    if (!selectedAccountId) return;
    setLoading(true);
    Api.comments.list(selectedAccountId)
      .then((list) => { setComments(list); setError(null); })
      .catch((e) => setError(e.message))
      .finally(() => setLoading(false));
  }, [selectedAccountId]);

  useEffect(load, [load]);

  const suggest = (comment) => {
    setBusy(comment.youtube_comment_id);
    Api.comments.suggestReply(comment.youtube_comment_id, comment.comment_text)
      .then((res) => setDrafts((d) => ({ ...d, [comment.youtube_comment_id]: res.suggested_reply })))
      .catch((e) => setError(e.message))
      .finally(() => setBusy(null));
  };

  const confirmReply = (comment) => {
    const text = drafts[comment.youtube_comment_id];
    if (!text) return;
    setBusy(comment.youtube_comment_id);
    Api.comments.reply(comment.youtube_comment_id, selectedAccountId, text)
      .then(() => { toast.show('Reply sent'); load(); })
      .catch((e) => setError(e.message))
      .finally(() => setBusy(null));
  };

  return (
    <div>
      <div className="page-title">Comments</div>
      <p>Un-replied comments, oldest first. Replies are never sent automatically — generate a suggestion, edit it if needed, then confirm.</p>
      {error && <div className="error-banner">{error}</div>}
      {loading && <div className="empty-state">Loading…</div>}
      {!loading && comments.length === 0 && !error && <div className="empty-state">No un-replied comments.</div>}

      {comments.map((c) => (
        <div className="card" key={c.youtube_comment_id}>
          <div><strong>{c.author_name}</strong> — {c.commented_at}</div>
          <p>{c.comment_text}</p>
          <textarea
            rows="2"
            style={{ width: '100%' }}
            placeholder="Suggested reply will appear here…"
            value={drafts[c.youtube_comment_id] || ''}
            onChange={(e) => setDrafts((d) => ({ ...d, [c.youtube_comment_id]: e.target.value }))}
          />
          <div style={{ display: 'flex', gap: 8, marginTop: 8 }}>
            <button className="btn" disabled={busy === c.youtube_comment_id} onClick={() => suggest(c)}>
              Suggest reply (AI)
            </button>
            <button
              className="btn btn-primary"
              disabled={busy === c.youtube_comment_id || !drafts[c.youtube_comment_id]}
              onClick={() => confirmReply(c)}
            >
              Confirm &amp; send
            </button>
          </div>
        </div>
      ))}
    </div>
  );
}
