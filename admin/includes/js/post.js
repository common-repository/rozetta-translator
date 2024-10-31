const handleAddPost = async () => {
  $.ajax({
    type: 'POST',
    url: `${postInfo.url}/?rest_route=/rozetta-wp-api/v1/post/${postInfo.id}/lang/${$('#rozettaNewPostLang').val()}`,
      success:function(html) {
        if (html.ID) {
          const searchParams = new URLSearchParams(window.location.search);
          searchParams.set('post', html.ID);
          const newRelativePathQuery = window.location.pathname + '?' + searchParams.toString();
          window.open(newRelativePathQuery, '_blank');
        } else {
          alert('Failed to create new post.');
        }
      }
  });
};

const selectPostLang = () => {
  const selectedValue = $('#rozettaPostLang').val();
  if (selectedValue === $('#rozettaNewPostLang').val()) {
    $('#rozettaNewPostLang').val('');
    $('#rozettaNewPostButton').hide();
  }

  $('#rozettaNewPostLang option').show();
  $(`#rozettaNewPostLang option[value=${selectedValue}]`).hide();
}

const selectNewPostLang = () => {
  $('#rozettaNewPostButton').show();
}

$( function() {
  selectPostLang();
});