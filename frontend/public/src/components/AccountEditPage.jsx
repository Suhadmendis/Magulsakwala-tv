function AccountEditPage({ accountId }) {
  const { refreshAccounts, setSelectedAccountId } = useAccountContext();
  const isNew = accountId === 'new';
  const [form, setForm] = useState({
    name: '', url: '', channel_id: '', api_key: '', api_secret: '', refresh_token: '',
  });
  const [loading, setLoading] = useState(!isNew);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState(null);
  const [confirmingDelete, setConfirmingDelete] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const toast = useToast();

  useEffect(() => {
    if (isNew) return;
    Api.accounts.get(accountId)
      .then((account) => { setForm(account); setLoading(false); })
      .catch((e) => { setError(e.message); setLoading(false); });
  }, [accountId, isNew]);

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    if (params.get('connected')) {
      toast.show('YouTube account connected');
      window.history.replaceState(null, '', window.location.pathname + window.location.hash);
    } else if (params.get('error')) {
      setError(`YouTube connection failed: ${params.get('error')}`);
      window.history.replaceState(null, '', window.location.pathname + window.location.hash);
    }
  }, []);

  const connectGoogle = () => {
    window.location.href = `${window.API_BASE || ''}/api/accounts/${accountId}/youtube/oauth/start`;
  };

  const update = (field) => (e) => setForm((f) => ({ ...f, [field]: e.target.value }));

  const save = () => {
    setSaving(true);
    setError(null);
    const action = isNew ? Api.accounts.create(form) : Api.accounts.update(accountId, form);
    action
      .then((account) => {
        refreshAccounts();
        setSelectedAccountId(account.id);
        toast.show(isNew ? 'Account added' : 'Account saved');
        navigate('video-operations');
      })
      .catch((e) => { setError(e.message); setSaving(false); });
  };

  const deleteAccount = () => {
    setDeleting(true);
    Api.accounts.remove(accountId)
      .then(() => {
        refreshAccounts();
        toast.show('Account deleted');
        navigate('video-operations');
      })
      .catch((e) => { setError(e.message); setDeleting(false); setConfirmingDelete(false); });
  };

  if (loading) return <div className="empty-state">Loading…</div>;

  return (
    <div>
      <div className="page-title">{isNew ? 'Add YouTube Account' : `Edit Account: ${form.name}`}</div>
      <div className="card" style={{ maxWidth: 480 }}>
        {error && <div className="error-banner">{error}</div>}
        <div className="field">
          <label>Channel name</label>
          <input value={form.name || ''} onChange={update('name')} />
        </div>
        <div className="field">
          <label>Channel URL</label>
          <input value={form.url || ''} onChange={update('url')} />
        </div>
        <div className="field">
          <label>YouTube channel ID</label>
          <input value={form.channel_id || ''} onChange={update('channel_id')} />
        </div>
        <div className="field">
          <label>API key (OAuth client ID)</label>
          <input value={form.api_key || ''} onChange={update('api_key')} />
        </div>
        <div className="field">
          <label>API secret (OAuth client secret)</label>
          <input type="password" value={form.api_secret || ''} onChange={update('api_secret')} />
        </div>
        <div className="field">
          <label>Refresh token</label>
          <input type="password" value={form.refresh_token || ''} onChange={update('refresh_token')} />
        </div>
        {!isNew && form.api_key && form.api_secret && (
          <div className="field">
            <button className="btn" onClick={connectGoogle}>
              {form.refresh_token ? 'Reconnect with Google' : 'Connect with Google'}
            </button>
            <div className="page-subtitle" style={{ marginTop: 4 }}>
              Redirects to Google's consent screen and fills in the refresh token automatically. Save the account (with API key/secret) first if you haven't.
            </div>
          </div>
        )}
        <div style={{ display: 'flex', gap: 8, justifyContent: 'space-between' }}>
          <div style={{ display: 'flex', gap: 8 }}>
            <button className="btn btn-primary" disabled={saving} onClick={save}>
              {saving ? 'Saving…' : 'Save'}
            </button>
            <button className="btn" onClick={() => navigate('video-operations')}>Cancel</button>
          </div>
          {!isNew && (
            <button className="btn btn-danger" onClick={() => setConfirmingDelete(true)}>Delete account</button>
          )}
        </div>
      </div>

      <ConfirmDialog
        open={confirmingDelete}
        title="Delete this account?"
        message="This removes the channel connection, its videos, thumbnails, and stored files. This can't be undone."
        busy={deleting}
        onConfirm={deleteAccount}
        onCancel={() => setConfirmingDelete(false)}
      />
    </div>
  );
}
