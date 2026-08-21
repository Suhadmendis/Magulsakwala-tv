// Inline SVG icons — no icon font/library (no build tooling in this project).
const SidebarIcons = {
  'video-operations': (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="2" y="5" width="14" height="14" rx="2" /><path d="M16 9.5 22 6v12l-6-3.5" /></svg>
  ),
  'content-feeder': (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M12 2v20M5 9l7-7 7 7" /></svg>
  ),
  comments: (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M21 12a8 8 0 1 1-3.5-6.6L21 4l-1 4.5A7.9 7.9 0 0 1 21 12Z" /></svg>
  ),
  analytics: (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M4 20V10M12 20V4M20 20v-7" /></svg>
  ),
  assets: (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="3" y="4" width="18" height="14" rx="2" /><circle cx="8.5" cy="9.5" r="1.5" /><path d="m21 15-5-4-4 3-3-2-6 5" /></svg>
  ),
  findings: (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="10.5" cy="10.5" r="6.5" /><path d="m21 21-4.35-4.35" /></svg>
  ),
  system: (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1.08-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09a1.65 1.65 0 0 0 1.51-1.08 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z" /></svg>
  ),
};

// Left sidebar: primary navigation + the Compose button.
function Sidebar({ activePage }) {
  const items = [
    { key: 'video-operations', label: 'Video Operations' },
    { key: 'content-feeder', label: 'Content Feeder' },
    { key: 'comments', label: 'Comments' },
    { key: 'analytics', label: 'Analytics' },
    { key: 'assets', label: 'Assets' },
    { key: 'findings', label: 'Findings' },
  ];

  return (
    <div className="sidebar">
      <div className="sidebar-nav">
        {items.map((item) => (
          <div
            key={item.key}
            className={'sidebar-item' + (activePage === item.key ? ' active' : '')}
            onClick={() => navigate(item.key)}
          >
            {SidebarIcons[item.key]}
            {item.label}
          </div>
        ))}
      </div>
      <button className="sidebar-compose-btn" onClick={() => navigate('compose')}>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M12 5v14M5 12h14" /></svg>
        Compose
      </button>
      <div
        className={'sidebar-item' + (activePage === 'system' ? ' active' : '')}
        style={{ marginTop: 'auto' }}
        onClick={() => navigate('system')}
      >
        {SidebarIcons.system}
        System
      </div>
    </div>
  );
}
