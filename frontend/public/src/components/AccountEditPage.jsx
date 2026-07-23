function AccountEditPage({ accountId }) {
  const { refreshAccounts, setSelectedAccountId } = useAccountContext();
  const isNew = accountId === 'new';
  const [form, setForm] = useState({
    name: '', url: '', channel_id: '', api_key: '', api_secret: '', refresh_token: '',
  });
  const [loading, setLoading] = useState(!isNew);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (isNew) return;
    Api.accounts.get(accountId)
      .then((account) => { setForm(account); setLoading(false); })
      .catch((e) => { setError(e.message); setLoading(false); });
  }, [accountId, isNew]);

  const update = (field) => (e) => setForm((f) => ({ ...f, [field]: e.target.value }));

  const save = () => {
    setSaving(true);
    setError(null);
    const action = isNew ? Api.accounts.create(form) : Api.accounts.update(accountId, form);
    action
      .then((account) => {
        refreshAccounts();
        setSelectedAccountId(account.id);
        navigate('video-operations');
      })
      .catch((e) => { setError(e.message); setSaving(false); });
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
        <div style={{ display: 'flex', gap: 8 }}>
          <button className="btn btn-primary" disabled={saving} onClick={save}>
            {saving ? 'Saving…' : 'Save'}
          </button>
          <button className="btn" onClick={() => navigate('video-operations')}>Cancel</button>
        </div>
      </div>
    </div>
  );
}
