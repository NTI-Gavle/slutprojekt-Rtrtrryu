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