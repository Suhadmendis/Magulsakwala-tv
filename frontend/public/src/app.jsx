// Root app: account context, hash-based routing, top bar + sidebar shell.
const { useState, useEffect, useCallback, createContext, useContext } = React;

const AccountContext = createContext(null);
function useAccountContext() {
  return useContext(AccountContext);
}

const PAGES = ['video-operations', 'comments', 'analytics', 'thumbnails', 'findings', 'compose', 'account-edit'];

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
    <AccountContext.Provider value={contextValue}>
      <TopBar />
      <div className="body-row">
        <Sidebar activePage={page} />
        <div className="main">
          {loadError && <div className="error-banner">{loadError}</div>}
          {!selectedAccountId && page !== 'account-edit' && (
            <div className="empty-state">No YouTube account selected yet. Add one from the account selector, top right.</div>
          )}
          {(selectedAccountId || page === 'account-edit') && (
            <PageRouter page={page} routeParam={routeParam} />
          )}
        </div>
      </div>
    </AccountContext.Provider>
  );
}

function PageRouter({ page, routeParam }) {
  switch (page) {
    case 'video-operations': return <VideoOperationsPage />;
    case 'comments': return <CommentsPage />;
    case 'analytics': return <AnalyticsPage />;
    case 'thumbnails': return <ThumbnailsEditorPage />;
    case 'findings': return <FindingsPage />;
    case 'compose': return <ComposeScreen />;
    case 'account-edit': return <AccountEditPage accountId={routeParam} />;
    default: return null;
  }
}

ReactDOM.createRoot(document.getElementById('root')).render(<App />);
