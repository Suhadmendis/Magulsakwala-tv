// Root app: account context, hash-based routing, top bar + sidebar shell.
const { useState, useEffect, useCallback, createContext, useContext } = React;

const AccountContext = createContext(null);
function useAccountContext() {
  return useContext(AccountContext);
}

const PAGES = ['video-operations', 'content-feeder', 'comments', 'analytics', 'assets', 'findings', 'compose', 'account-edit', 'system'];

function getCurrentPage() {
  const hash = window.location.hash.replace('#/', '');
  const page = hash.split('/')[0];
  return PAGES.includes(page) ? page : 'video-operations';
}

function getRouteParam() {
  const hash = window.location.hash.replace('#/', '');
  const parts = hash.split('/');
  return parts[1] || null;
}

function navigate(page, param) {
  window.location.hash = param ? `/${page}/${param}` : `/${page}`;
}

// Shared status-dot component — the one recurring motif for a video's
// lifecycle state, used identically in every table/card/modal that shows it.
function StatusPill({ status }) {
  return <span className={'status-pill status-' + status}>{status}</span>;
}

function App() {
  const [accounts, setAccounts] = useState([]);
  const [selectedAccountId, setSelectedAccountId] = useState(null);
  const [page, setPage] = useState(getCurrentPage());
  const [routeParam, setRouteParam] = useState(getRouteParam());
  const [loadError, setLoadError] = useState(null);

  const refreshAccounts = useCallback(() => {
    Api.accounts.list()
      .then((list) => {
        setAccounts(list);
        setLoadError(null);
        setSelectedAccountId((current) => {
          if (current && list.some((a) => a.id === current)) return current;
          return list.length ? list[0].id : null;
        });
      })
      .catch((e) => setLoadError(e.message));
  }, []);

  useEffect(() => { refreshAccounts(); }, [refreshAccounts]);

  useEffect(() => {
    const onHashChange = () => {
      setPage(getCurrentPage());
      setRouteParam(getRouteParam());
    };
    window.addEventListener('hashchange', onHashChange);
    return () => window.removeEventListener('hashchange', onHashChange);
  }, []);

  const contextValue = { accounts, selectedAccountId, setSelectedAccountId, refreshAccounts };

  return (
    <ToastProvider>
      <AccountContext.Provider value={contextValue}>
        <TopBar />
        <div className="body-row">
          <Sidebar activePage={page} />
          <div className="main">
            {loadError && <div className="error-banner">{loadError}</div>}
            {!selectedAccountId && page !== 'account-edit' && page !== 'system' && (
              <div className="empty-state">No YouTube account connected yet. Add one from the account selector, top right.</div>
            )}
            {(selectedAccountId || page === 'account-edit' || page === 'system') && (
              <PageRouter page={page} routeParam={routeParam} />
            )}
          </div>
        </div>
      </AccountContext.Provider>
    </ToastProvider>
  );
}

function PageRouter({ page, routeParam }) {
  switch (page) {
    case 'video-operations': return <VideoOperationsPage />;
    case 'content-feeder': return <ContentFeederPage />;
    case 'comments': return <CommentsPage />;
    case 'analytics': return <AnalyticsPage />;
    case 'assets': return <AssetsPage />;
    case 'findings': return <FindingsPage />;
    case 'compose': return <ComposeScreen />;
    case 'account-edit': return <AccountEditPage accountId={routeParam} />;
    case 'system': return <SystemPage />;
    default: return null;
  }
}

ReactDOM.createRoot(document.getElementById('root')).render(<App />);
