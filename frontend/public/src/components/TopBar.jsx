// Minimal top bar: just the YouTube account select box, right-aligned.
function TopBar() {
  const { accounts, selectedAccountId, setSelectedAccountId } = useAccountContext();
  const [open, setOpen] = useState(false);

  const selected = accounts.find((a) => a.id === selectedAccountId);

  return (
    <div className="topbar">
      <div className="account-select-box">
        <button className="account-select-button" onClick={() => setOpen((o) => !o)}>
          {selected ? selected.name : 'Select YouTube account'} ▾
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
