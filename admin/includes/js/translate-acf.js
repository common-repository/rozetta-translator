const traverseTextNodeInACFGroups = (element, callback, isEntryValue = false) => {
  for (const node of element.childNodes) {
    if (node.tagName in ignore) continue;

    switch (node.nodeType) {
      case Node.ELEMENT_NODE:
        if (node.nodeName === 'INPUT' && node.getAttribute('type') === 'text' && node.getAttribute('id') && node.getAttribute('id').startsWith('acf-field')) {
            callback($(node).val(), node);
        }
        if (node.tagName === 'IFRAME' && $(node.parentNode.parentNode.parentNode).css('display') !== 'none') {
            callback('', node);
            break;
        }
        if (node.tagName === 'TEXTAREA' && (!$(node).attr('aria-hidden') || $(node).attr('aria-hidden') === 'false')) {
            callback('', node);
            break;
        }
        traverseTextNodeInACFGroups(node, callback, isEntryValue);
        break;
      case Node.TEXT_NODE:
        if (isEntryValue) {
            callback(node.nodeValue, node);
        }
        break;
      case Node.DOCUMENT_NODE:
        traverseTextNodeInACFGroups(node, callback, isEntryValue);
    }
  }
};


const getFromACFFields = async () => {
    const extractText = (text, node) => {
        if (node.tagName === 'IFRAME') {
            const newDiv = document.createElement('div');
            newDiv.innerHTML = $(node).contents().find('#tinymce').html() || '';
            traverseTextNodeInACFGroups(newDiv, extractText, true);
        }
        if (node.tagName === 'TEXTAREA') {
            const newDiv = document.createElement('div');
            newDiv.innerHTML = $(node).val() || '';
            traverseTextNodeInACFGroups(newDiv, extractText, true);
        }
        if (text && text.replace(/[\n\t\r]/g, '').trim() !== '') {
            text.split('\n').map(o => {
                if (o !== '') {
                    originalTextArray.push(o);
                }
            });
        }
    };

    const originalTextArray = [];
    acfInfo.acfGroups.map(o => {
        traverseTextNodeInACFGroups($(`#acf-${o.key}`).get(0), extractText);
    });
    return originalTextArray;
    //console.log(envInfo.acfGroups);
};

const setFromACFFields = async (translatedArray) => {
    let i = 0;
    const backfillTranslatedText = (text, node) => {
        if (node.tagName === 'IFRAME') {
            const newDiv = document.createElement('div');
            newDiv.innerHTML = $(node).contents().find('#tinymce').html() || '';
            traverseTextNodeInACFGroups(newDiv, backfillTranslatedText, true);
            $(node).contents().find('#tinymce').html(newDiv.innerHTML);
        } else if (node.tagName === 'TEXTAREA') {
            const newDiv = document.createElement('div');
            newDiv.innerHTML = $(node).val() || '';
            traverseTextNodeInACFGroups(newDiv, backfillTranslatedText, true);
            $(node).val(newDiv.innerHTML);
        } else if (node.nodeName === 'INPUT' && $(node).val() !== '') {
            $(node).val(translatedArray[i]);
            i++;
        } else if (node) {
            if (node.textContent && node.textContent.replace(/[\n\t\r]/g, '').trim() !== '') {
                let tempText = '';
                node.textContent.split('\n').map((o, j) => {
                    if (o !== '') {
                        tempText += translatedArray[i];
                        i++;
                    }
                    if (j !== node.textContent.split('\n').length - 1) {
                        tempText += '\n';
                    }
                });
                node.textContent = tempText;
            }
        }
    };
    acfInfo.acfGroups.map(o => {
        traverseTextNodeInACFGroups($(`#acf-${o.key}`).get(0), backfillTranslatedText);
    });
};


const copyFromACFFields = async (originalId, currentId, confirmedText = "Are you sure to copy the Customized Field to current post from Original Post?") => {
    if (confirm(confirmedText)) {
        $.ajax({
          type: 'POST',
          url: `${postInfo.url}/?rest_route=/rozetta-wp-api/v1/copy/${originalId}/${currentId}`,
            success:function(html) {
              if (html) {
                window.location.reload();
              } else {
                alert('Failed to grant data.');
              }
            }
        });
    }
};
