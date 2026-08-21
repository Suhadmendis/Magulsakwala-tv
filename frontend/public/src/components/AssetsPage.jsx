function AssetGallery({ title, items, emptyLabel, imageField }) {
  return (
    <>
      <div className="page-title" style={{ fontSize: 16 }}>{title}</div>
      <div className="thumbnail-grid">
        {items.map((item) => (
          <div key={item.id} className="card thumbnail-card">
            {imageField === 'file' ? (
              item[imageField] && <div className="mono" style={{ fontSize: 12 }}>{item.name || item[imageField].split('/').pop()}</div>
            ) : (
              item[imageField] && (
                <img loading="lazy" width="220" height="124" src={storageUrl(item[imageField])} style={{ width: '100%', height: 'auto', borderRadius: 6 }} alt="" />
              )
            )}
            <div className="mono" style={{ fontSize: 12, marginTop: 6 }}>{item.reference_no}</div>
          </div>
        ))}
        {items.length === 0 && <div className="empty-state">{emptyLabel}</div>}
      </div>
    </>
  );
}

function GenerateBox({ categoryKey, accountId, onGenerated }) {
  const [prompt, setPrompt] = useState('');
  const [generating, setGenerating] = useState(false);
  const [error, setError] = useState(null);
  const toast = useToast();

  const generate = () => {
    if (!prompt.trim()) {
      setError('Enter a prompt first.');
      return;
    }
    setGenerating(true);
    setError(null);
    Api.assets.generate(categoryKey, { channel_id: accountId, prompt })
      .then((asset) => { toast.show(`${asset.reference_no} generated`); setPrompt(''); onGenerated(); })
      .catch((e) => setError(e.message))
      .finally(() => setGenerating(false));
  };

  return (
    <div className="card">
      {error && <div className="error-banner">{error}</div>}
      <div className="field">
        <label>Generate with AI (local SD-Turbo)</label>
        <input
          placeholder="Describe the image…"
          value={prompt}
          onChange={(e) => setPrompt(e.target.value)}
        />
      </div>
      <button className="btn btn-primary" disabled={generating} onClick={generate}>
        {generating ? 'Generating…' : 'Generate'}
      </button>
    </div>
  );
}

// Western astrology asset categories — reusable images/fonts for building
// thumbnails. 'thumbnails' is backed by m_thumbnails (Api.thumbnailPresets);
// the rest are backed by their own table via Api.assets (migrations 0006/0007).
const ASSET_CATEGORIES = [
  { key: 'thumbnails', label: 'Thumbnails', imageField: 'rendered_image' },
  { key: 'zodiac-signs', label: 'Zodiac Signs', imageField: 'image' },
  { key: 'planets', label: 'Planets', imageField: 'image' },
  { key: 'elements', label: 'Elements', imageField: 'image' },
  { key: 'moon-phases', label: 'Moon Phases', imageField: 'image' },
  { key: 'backgrounds', label: 'Backgrounds', imageField: 'image' },
  { key: 'fonts', label: 'Fonts', imageField: 'file' },
];

function AssetsPage() {
  const { selectedAccountId } = useAccountContext();
  const [view, setView] = useState('menu');
  const [items, setItems] = useState([]);
  const [error, setError] = useState(null);

  const category = ASSET_CATEGORIES.find((c) => c.key === view);

  const loadItems = useCallback(() => {
    if (!category || !selectedAccountId) return;
    const load = category.key === 'thumbnails'
      ? Api.thumbnailPresets.list(selectedAccountId)
      : Api.assets.list(category.key, selectedAccountId);
    load.then(setItems).catch((e) => setError(e.message));
  }, [view, selectedAccountId]);

  useEffect(loadItems, [loadItems]);

  const canGenerate = category && category.key !== 'thumbnails' && category.imageField === 'image';

  return (
    <div>
      <div className="page-title">Assets</div>
      {error && <div className="error-banner">{error}</div>}

      {view === 'menu' && (
        <div className="card">
          {ASSET_CATEGORIES.map((c) => (
            <div key={c.key} className="list-row" onClick={() => setView(c.key)}>{c.label}</div>
          ))}
        </div>
      )}

      {category && (
        <>
          <button className="btn" style={{ marginBottom: 12 }} onClick={() => setView('menu')}>← Back</button>
          {canGenerate && (
            <GenerateBox categoryKey={category.key} accountId={selectedAccountId} onGenerated={loadItems} />
          )}
          <AssetGallery
            title={category.label}
            items={items}
            imageField={category.imageField}
            emptyLabel={`No ${category.label.toLowerCase()} assets yet.`}
          />
        </>
      )}
    </div>
  );
}
