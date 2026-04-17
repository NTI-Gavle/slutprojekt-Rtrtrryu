// Javascripty

function addPost() {
  const container = document.getElementById('post-container');

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

function Like(post_id) {
  fetch(`../public/Like.php?post_id=${post_id}`)
    .then(response => response.text()).then(data => {
      RefreshLikes(post_id);
    })
}

function RefreshLikes(post_id) {
  console.log('Ran refresh likes!')
  fetch(`../public/Likesbackend.php?post_id=${post_id}`)
    .then(response => response.json()).then(data => {
      const like_box = document.getElementById('like')
      if (data.status == true) {
        like_box.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-heart-fill mx-auto my-auto" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 1.314C12.438-3.248 23.534 4.735 8 15-7.534 4.736 3.562-3.248 8 1.314"/></svg>'
      } else {
        like_box.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-heart" viewBox="0 0 16 16"><path d="m8 2.748-.717-.737C5.6.281 2.514.878 1.4 3.053c-.523 1.023-.641 2.5.314 4.385.92 1.815 2.834 3.989 6.286 6.357 3.452-2.368 5.365-4.542 6.286-6.357.955-1.886.838-3.362.314-4.385C13.486.878 10.4.28 8.717 2.01zM8 15C-7.333 4.868 3.279-3.04 7.824 1.143q.09.083.176.171a3 3 0 0 1 .176-.17C12.72-3.042 23.333 4.867 8 15"/></svg>'
      }
      const like_counter = document.getElementById("like_counter");
      like_counter.innerText = data.likes;
    })
}
//zoom img function
document.addEventListener('click', (e) => {
  const preview = e.target.closest('.post-image-preview');
  const box = document.getElementById('imageLightbox');
  const big = document.getElementById('lightboxImg');

  if (preview && box && big) {
      big.src = preview.src;
      box.classList.add('show');
      return;
  }

  if (e.target.id === 'imageLightbox' || e.target.id === 'lightboxImg') {
      box.classList.remove('show');
      big.src = '';
  }
});
function openLightbox(src) {
  const box = document.getElementById('imageLightbox');
  const img = document.getElementById('lightboxImg');
  img.src = src;
  box.classList.add('show');
}
function closeLightbox() {
  const box = document.getElementById('imageLightbox');
  const img = document.getElementById('lightboxImg');
  box.classList.remove('show');
  img.src = '';
}
