// Plain JS (no JSX) — fetch wrapper for the PHP REST API.
// Set window.API_BASE before this script loads to point at a different
// origin than the one serving this frontend (see index.html).

(function () {
  const BASE = window.API_BASE || '';

  async function request(method, path, { json, form, query } = {}) {
    let url = BASE + path;
    if (query) {
      const qs = new URLSearchParams(
        Object.fromEntries(Object.entries(query).filter(([, v]) => v !== undefined && v !== null))
      ).toString();
      if (qs) url += '?' + qs;
    }

    const options = { method };
    if (form) {
      options.body = form;
    } else if (json !== undefined) {
      options.headers = { 'Content-Type': 'application/json' };
      options.body = JSON.stringify(json);
    }

    const response = await fetch(url, options);
    const text = await response.text();
    const data = text ? JSON.parse(text) : null;

    if (!response.ok) {
      const message = (data && data.error) || `Request failed (${response.status})`;
      throw new Error(message);
    }
    return data;
  }

  // Server-side code stores absolute filesystem paths (e.g.
  // "/var/www/app/storage/accounts/1/thumbnails/x.png"). Convert to a URL
  // servable by the backend's public/storage symlink.
  window.storageUrl = function (absolutePath) {
    if (!absolutePath) return null;
    const marker = 'storage/accounts/';
    const index = absolutePath.indexOf(marker);
    if (index === -1) return absolutePath;
    return BASE + '/' + absolutePath.slice(index);
  };

  window.Api = {
    accounts: {
      list: () => request('GET', '/api/accounts'),
      create: (body) => request('POST', '/api/accounts', { json: body }),
      get: (id) => request('GET', `/api/accounts/${id}`),
      update: (id, body) => request('PUT', `/api/accounts/${id}`, { json: body }),
      remove: (id) => request('DELETE', `/api/accounts/${id}`),
    },
    videos: {
      list: (accountId) => request('GET', '/api/videos', { query: { account_id: accountId } }),
      search: (accountId, { video_type, q } = {}) =>
        request('GET', '/api/videos/search', { query: { account_id: accountId, video_type, q } }),
      get: (id) => request('GET', `/api/videos/${id}`),
      update: (id, body) => request('PUT', `/api/videos/${id}`, { json: body }),
      schedule: (id, scheduledAt) => request('PUT', `/api/videos/${id}/schedule`, { json: { scheduled_at: scheduledAt } }),
      remove: (id) => request('DELETE', `/api/videos/${id}`),
      upload: (form) => request('POST', '/api/videos/upload', { form }),
    },
    compose: {
      next: (accountId) => request('GET', '/api/compose/next', { query: { account_id: accountId } }),
      postNext: (accountId) => request('POST', '/api/videos/post-next', { json: { account_id: accountId } }),
    },
    findings: {
      import: (accountId, elements) => request('POST', '/api/findings/import', { json: { account_id: accountId, elements } }),
    },
    comments: {
      list: (accountId) => request('GET', '/api/comments', { query: { account_id: accountId } }),
      suggestReply: (commentId, commentText) =>
        request('POST', `/api/comments/${commentId}/suggest-reply`, { json: { comment_text: commentText } }),
      reply: (commentId, accountId, replyText) =>
        request('POST', `/api/comments/${commentId}/reply`, { json: { account_id: accountId, reply_text: replyText } }),
    },
    content: {
      generateTitle: (videoId) => request('POST', '/api/content/generate/title', { json: { video_id: videoId } }),
      generateDescription: (videoId) => request('POST', '/api/content/generate/description', { json: { video_id: videoId } }),
      generateTags: (videoId) => request('POST', '/api/content/generate/tags', { json: { video_id: videoId } }),
      generateContent: (videoId) => request('POST', '/api/content/generate/content', { json: { video_id: videoId } }),
      generateVoiceOver: (videoId) => request('POST', '/api/content/generate/voice-over', { json: { video_id: videoId } }),
    },
    thumbnails: {
      list: (accountId) => request('GET', '/api/thumbnails', { query: { account_id: accountId } }),
      save: (form) => request('POST', '/api/thumbnails', { form }),
      remove: (id) => request('DELETE', `/api/thumbnails/${id}`),
    },
    analytics: {
      channel: (accountId) => request('GET', '/api/analytics/channel', { query: { account_id: accountId } }),
      video: (videoId) => request('GET', `/api/analytics/videos/${videoId}`),
    },
  };
})();
