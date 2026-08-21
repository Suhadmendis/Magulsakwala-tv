function StatRows({ stats }) {
  if (!stats) return null;
  if (stats.error) return <span className="error-banner" style={{ margin: 0 }}>{stats.error}</span>;
  return (
    <table className="table">
      <tbody>
        {Object.entries(stats).map(([key, value]) => (
          <tr key={key}><th>{key.replace(/_/g, ' ')}</th><td className="mono">{String(value)}</td></tr>
        ))}
      </tbody>
    </table>
  );
}

function AnalyticsPage() {
  const { selectedAccountId } = useAccountContext();
  const [channelStats, setChannelStats] = useState(null);
  const [videos, setVideos] = useState([]);
  const [videoStats, setVideoStats] = useState({});
  const [error, setError] = useState(null);

  useEffect(() => {
    if (!selectedAccountId) return;
    Api.analytics.channel(selectedAccountId).then(setChannelStats).catch((e) => setError(e.message));
    Api.videos.list(selectedAccountId)
      .then((list) => setVideos(list.filter((v) => v.status === 'published')))
      .catch((e) => setError(e.message));
  }, [selectedAccountId]);

  const loadVideoStats = (videoId) => {
    Api.analytics.video(videoId)
      .then((stats) => setVideoStats((s) => ({ ...s, [videoId]: stats })))
      .catch((e) => setVideoStats((s) => ({ ...s, [videoId]: { error: e.message } })));
  };

  return (
    <div>
      <div className="page-title">Analytics</div>
      <p>Live from the YouTube API — nothing here is cached.</p>
      {error && <div className="error-banner">{error}</div>}

      <div className="card">
        <div className="eyebrow" style={{ marginBottom: 8 }}>Channel</div>
        {!channelStats && <div className="skeleton-line" style={{ width: '60%' }} />}
        <StatRows stats={channelStats} />
      </div>

      <div className="card">
        <div className="eyebrow" style={{ marginBottom: 8 }}>Published videos</div>
        <div className="table-scroll">
          <table className="table">
            <thead><tr><th>Title</th><th>Stats</th><th></th></tr></thead>
            <tbody>
              {videos.map((v) => (
                <tr key={v.id}>
                  <td>{v.title}</td>
                  <td>{videoStats[v.id] ? <StatRows stats={videoStats[v.id]} /> : '—'}</td>
                  <td><button className="btn" onClick={() => loadVideoStats(v.id)}>Load stats</button></td>
                </tr>
              ))}
              {videos.length === 0 && <tr><td colSpan="3" className="empty-state">No published videos yet.</td></tr>}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
