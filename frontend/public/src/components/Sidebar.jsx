// Left sidebar: primary navigation + the Compose button.
function Sidebar({ activePage }) {
  const items = [
    { key: 'video-operations', label: 'Video Operations' },
    { key: 'comments', label: 'Comments' },
    { key: 'analytics', label: 'Analytics' },
    { key: 'thumbnails', label: 'Thumbnails' },
    { key: 'findings', label: 'Findings' },
  ];

  return (
    <div className="sidebar">
      {items.map((item) => (
        <div
          key={item.key}
          className={'sidebar-item' + (activePage === item.key ? ' active' : '')}
          onClick={() => navigate(item.key)}
        >
          {item.label}
        </div>
      ))}
      <button className="sidebar-compose-btn" onClick={() => navigate('compose')}>
        Compose
      </button>
    </div>
  );
}
