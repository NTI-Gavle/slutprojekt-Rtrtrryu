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

  function like(post_id)
  {
    fetch(`../Like.php?post_id=${post_id}`)
      .then(response => response.text()).then(data =>{
        RefreshLikes(post_id);
      })
  }

  function RefreshLikes(post_id){
    fetch(`../Likesbackend.php?post_id=${post_id}`)
      .then(response => response.json()).then(data =>{
          document.getElementById('lie')


      })
  }
  