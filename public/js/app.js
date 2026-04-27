// Shared site JavaScript

const SiteApp = (() => {
  const SVG_HEART_FILLED = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-heart-fill mx-auto my-auto" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 1.314C12.438-3.248 23.534 4.735 8 15-7.534 4.736 3.562-3.248 8 1.314"/></svg>';
  const SVG_HEART_EMPTY = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-heart" viewBox="0 0 16 16"><path d="m8 2.748-.717-.737C5.6.281 2.514.878 1.4 3.053c-.523 1.023-.641 2.5.314 4.385.92 1.815 2.834 3.989 6.286 6.357 3.452-2.368 5.365-4.542 6.286-6.357.955-1.886.838-3.362.314-4.385C13.486.878 10.4.28 8.717 2.01zM8 15C-7.333 4.868 3.279-3.04 7.824 1.143q.09.083.176.171a3 3 0 0 1 .176-.17C12.72-3.042 23.333 4.867 8 15"/></svg>';
  const COMMENT_LIMIT = 5;

  function byId(id) {
    return document.getElementById(id);
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  async function requestJson(url, options = {}) {
    const response = await fetch(url, options);
    const text = await response.text();

    try {
      return JSON.parse(text);
    } catch {
      throw new Error(text || 'Invalid server response');
    }
  }

  function setLightboxVisible(visible, src = '') {
    const box = byId('imageLightbox');
    const img = byId('lightboxImg');
    if (!box || !img) return;

    box.classList.toggle('show', visible);
    img.src = visible ? src : '';
  }

  function renderComment(comment) {
    const row = document.createElement('div');
    row.className = 'd-flex gap-3 border rounded-3 p-2 mb-2 bg-light-subtle comment-item';
    row.id = `comment-${comment.id}`;

    const avatarHtml = comment.avatar_path
      ? `<img src="${escapeHtml(comment.avatar_path)}" alt="pfp" class="rounded-circle border" style="width:48px;height:48px;object-fit:cover;">`
      : `<div class="rounded-circle border bg-dark text-white d-grid place-items-center" style="width:48px;height:48px;display:grid;">Pfp</div>`;

    const deleteHtml = comment.can_delete
      ? `<div class="ms-auto align-self-start comment-actions"><button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteComment(${Number(comment.id)})">Delete</button></div>`
      : '';

    row.innerHTML = `
      <div class="text-center" style="min-width:72px;">
        ${avatarHtml}
        <div class="small mt-1">
          <a href="Profile.php?user_id=${Number(comment.author_id || 0)}" class="text-decoration-none text-reset">${escapeHtml(comment.username)}</a>
        </div>
      </div>
      <div class="flex-grow-1 comment-content">
        <p class="mb-1 comment-body">${escapeHtml(comment.body).replace(/\n/g, '<br>')}</p>
        <small class="text-muted">${escapeHtml(comment.created_at)}</small>
      </div>
      ${deleteHtml}
    `;

    return row;
  }

  async function refreshLikes(postId) {
    const likeBox = byId('like');
    const likeCounter = byId('like_counter');
    if (!likeBox || !likeCounter) return;

    try {
      const data = await requestJson(`Likesbackend.php?post_id=${encodeURIComponent(postId)}`);
      likeBox.innerHTML = data.status === true ? SVG_HEART_FILLED : SVG_HEART_EMPTY;
      likeCounter.innerText = String(data.likes ?? '');
    } catch (error) {
      console.error('RefreshLikes failed', error);
    }
  }

  async function like(postId) {
    try {
      await fetch(`Like.php?post_id=${encodeURIComponent(postId)}`);
      await refreshLikes(postId);
    } catch (error) {
      console.error('Like failed', error);
    }
  }

  function addPost() {
    const container = byId('post-container');
    if (!container) return;

    const post = document.createElement('div');
    post.className = 'post';
    post.innerHTML = `
      <div class="post-header"></div>
      <div class="post-content"></div>
      <div class="reply">
        <div class="likes"></div>
        <div class="comment"></div>
      </div>
    `;

    container.appendChild(post);
  }

  function openNav() {
    const sideNav = byId('mySidenav');
    const header = byId('header');
    if (sideNav) sideNav.style.width = '250px';
    if (header) header.classList.add('openNav');
  }

  function closeNav() {
    const sideNav = byId('mySidenav');
    const header = byId('header');
    if (sideNav) sideNav.style.width = '0';
    if (header) header.classList.remove('openNav');
  }

  function openLightbox(src) {
    setLightboxVisible(true, src);
  }

  function closeLightbox() {
    setLightboxVisible(false);
  }

  async function postComment(event) {
    event.preventDefault();

    const form = byId('commentForm');
    const list = byId('commentsList');
    const loadMoreBtn = byId('loadMoreBtn');
    if (!form || !list) return false;

    try {
      const data = await requestJson('add-comment.php', {
        method: 'POST',
        body: new FormData(form)
      });

      if (!data.ok) {
        alert(data.error || 'Could not post');
        return false;
      }

      list.prepend(renderComment(data.comment));
      form.reset();

      if (loadMoreBtn) {
        loadMoreBtn.dataset.offset = String(Number(loadMoreBtn.dataset.offset || 0) + 1);
      }
    } catch (error) {
      console.error('postComment failed', error);
      alert(error.message || 'Could not post');
    }

    return false;
  }

  async function deleteComment(commentId) {
    if (!confirm('Delete this comment?')) return;

    try {
      const payload = new FormData();
      payload.append('comment_id', String(commentId));

      const data = await requestJson('delete-comment.php', {
        method: 'POST',
        body: payload
      });

      if (!data.ok) {
        alert(data.error || 'Could not delete comment');
        return;
      }

      const row = byId(`comment-${commentId}`);
      if (row) row.remove();
    } catch (error) {
      console.error('deleteComment failed', error);
      alert(error.message || 'Could not delete comment');
    }
  }

  async function loadMoreComments(button) {
    const list = byId('commentsList');
    if (!button || !list) return;

    const postId = button.dataset.postId;
    const offset = button.dataset.offset || 0;

    try {
      const data = await requestJson(`comments.php?post_id=${encodeURIComponent(postId)}&offset=${encodeURIComponent(offset)}&limit=${COMMENT_LIMIT}`);

      if (!data.ok) {
        alert(data.error || 'Could not load comments');
        return;
      }

      data.comments.forEach((comment) => list.appendChild(renderComment(comment)));
      button.dataset.offset = String(Number(offset) + data.comments.length);

      if (!data.hasMore) {
        button.classList.add('d-none');
      }
    } catch (error) {
      console.error('loadMoreComments failed', error);
      alert(error.message || 'Could not load comments');
    }
  }

  function initPageFeatures() {
    const body = document.body;
    if (body?.dataset?.postId) {
      refreshLikes(body.dataset.postId);
    }

    document.addEventListener('click', (event) => {
      const actionTarget = event.target.closest('[data-action]');
      if (!actionTarget) return;

      const action = actionTarget.dataset.action;
      if (action === 'open-nav') {
        event.preventDefault();
        openNav();
        return;
      }

      if (action === 'close-nav') {
        event.preventDefault();
        closeNav();
        return;
      }

      if (action === 'like') {
        event.preventDefault();
        like(actionTarget.dataset.postId);
        return;
      }

      if (action === 'delete-comment') {
        event.preventDefault();
        deleteComment(actionTarget.dataset.commentId);
      }
    });

    document.addEventListener('submit', (event) => {
      const form = event.target.closest('form[data-confirm]');
      if (!form) return;

      const message = form.dataset.confirm || 'Are you sure?';
      if (!confirm(message)) {
        event.preventDefault();
      }
    });

    const commentForm = byId('commentForm');
    if (commentForm) {
      commentForm.addEventListener('submit', postComment);
    }

    const loadMoreBtn = byId('loadMoreBtn');
    if (loadMoreBtn) {
      loadMoreBtn.addEventListener('click', () => loadMoreComments(loadMoreBtn));
    }

    const preview = document.querySelector('.post-image-preview');
    if (preview) {
      preview.addEventListener('click', () => openLightbox(preview.src));
    }

    const lightbox = byId('imageLightbox');
    if (lightbox) {
      lightbox.addEventListener('click', (event) => {
        if (event.target.id === 'imageLightbox' || event.target.id === 'lightboxImg') {
          closeLightbox();
        }
      });
    }
  }

  return {
    addPost,
    refreshLikes,
    like,
    openNav,
    closeNav,
    openLightbox,
    closeLightbox,
    postComment,
    deleteComment,
    loadMoreComments,
    initPageFeatures,
  };
})();

document.addEventListener('DOMContentLoaded', SiteApp.initPageFeatures);

window.addPost = SiteApp.addPost;
window.Like = SiteApp.like;
window.RefreshLikes = SiteApp.refreshLikes;
window.openLightbox = SiteApp.openLightbox;
window.closeLightbox = SiteApp.closeLightbox;
window.openNav = SiteApp.openNav;
window.closeNav = SiteApp.closeNav;
window.postComment = SiteApp.postComment;
window.deleteComment = SiteApp.deleteComment;
