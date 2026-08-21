// Minimal toast/notification stack. Mounted once in app.jsx; any component
// calls useToast() to show a success/error message.
// React.createContext (not the destructured form) — this line runs at script
// load time, before app.jsx's `const { createContext } = React` has executed.
const ToastContext = React.createContext(null);

function useToast() {
  return useContext(ToastContext);
}

function ToastProvider({ children }) {
  const [toasts, setToasts] = useState([]);

  const dismiss = useCallback((id) => {
    setToasts((list) => list.filter((t) => t.id !== id));
  }, []);

  const show = useCallback((message, { type = 'success', duration = 3200 } = {}) => {
    const id = Date.now() + Math.random();
    setToasts((list) => [...list, { id, message, type }]);
    setTimeout(() => dismiss(id), duration);
  }, [dismiss]);

  return (
    <ToastContext.Provider value={{ show }}>
      {children}
      <div className="toast-stack">
        {toasts.map((t) => (
          <div key={t.id} className={'toast' + (t.type === 'error' ? ' toast-error' : '')} onClick={() => dismiss(t.id)}>
            {t.message}
          </div>
        ))}
      </div>
    </ToastContext.Provider>
  );
}
