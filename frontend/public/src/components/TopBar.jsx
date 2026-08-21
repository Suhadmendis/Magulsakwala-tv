function initials(name) {
  if (!name) return '?';
  const parts = name.trim().split(/\s+/);
  return (parts[0][0] + (parts[1] ? parts[1][0] : '')).toUpperCase();
}

function getCurrentTheme() {
  const saved = localStorage.getItem('theme');
  if (saved === 'light' || saved === 'dark') return saved;
  return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

// Minimal top bar: theme toggle + the YouTube account select box, right-aligned.
function TopBar() {
  const { accounts, selectedAccountId, setSelectedAccountId } = useAccountContext();
  const [open, setOpen] = useState(false);
  const [theme, setTheme] = useState(getCurrentTheme);
  const boxRef = React.useRef(null);

  const toggleTheme = () => {
    const next = theme === 'dark' ? 'light' : 'dark';
    setTheme(next);
    localStorage.setItem('theme', next);
    document.documentElement.setAttribute('data-theme', next);
  };

  const selected = accounts.find((a) => a.id === selectedAccountId);

  useEffect(() => {
    if (!open) return;
    const onKeyDown = (e) => { if (e.key === 'Escape') setOpen(false); };
    const onClickOutside = (e) => {
      if (boxRef.current && !boxRef.current.contains(e.target)) setOpen(false);
    };
    window.addEventListener('keydown', onKeyDown);
    window.addEventListener('mousedown', onClickOutside);
    return () => {
      window.removeEventListener('keydown', onKeyDown);
      window.removeEventListener('mousedown', onClickOutside);
    };
  }, [open]);

  return (
    <div className="topbar">
      <button
        className="theme-toggle-btn"
        onClick={toggleTheme}
        title={theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'}
        aria-label={theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'}
      >
        {theme === 'dark' ? (
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="4" /><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41" /></svg>
        ) : (
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" /></svg>
        )}
      </button>
      <div className="account-select-box" ref={boxRef}>
        <button
          className="account-select-button"
          onClick={() => setOpen((o) => !o)}
          aria-expanded={open}
          aria-haspopup="true"
        >
          <span className="account-badge">{selected ? initials(selected.name) : '＋'}</span>
          {selected ? selected.name : 'Select YouTube account'}
        </button>
        {open && (
          <div className="account-dropdown">
            {accounts.map((account) => (
              <div
                key={account.id}
                className={'account-dropdown-row' + (account.id === selectedAccountId ? ' selected' : '')}
              >
                <button
                  className="account-edit-btn"
                  title="Edit this account"
                  aria-label={`Edit ${account.name}`}
                  onClick={(e) => {
                    e.stopPropagation();
                    setOpen(false);
                    navigate('account-edit', account.id);
                  }}
                >
                  ✎
                </button>
                <span
                  style={{ flex: 1, marginLeft: 8, cursor: 'pointer' }}
                  onClick={() => { setSelectedAccountId(account.id); setOpen(false); }}
                >
                  {account.name}
                </span>
              </div>
            ))}
            {accounts.length === 0 && <div className="account-dropdown-row">No accounts yet</div>}
            <div className="account-dropdown-footer">
              <button
                className="btn"
                style={{ width: '100%' }}
                onClick={() => { setOpen(false); navigate('account-edit', 'new'); }}
              >
                + Add account
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
