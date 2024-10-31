const $ = jQuery;
const apiUrl = 'https://translate.rozetta-api.io';
let originalTitle = '';
let originalHtml = '';
let originalACFText = [];

const showErrorMessage = (message) => {
    $('#rozetta-translator-setting-before').show();
    $('#rozetta-translator-status-translating').hide();
    $('#rozetta-translator-error-message').show();
    $('#rozetta-translator-error-message').html(message);
}

const fetchData = async (url, method, headers, data) => {
    try {
        const response = await fetch(url, {
        method,
        headers,
        body: JSON.stringify(data),
        });
        const json = await response
        .json()
        .then();
        return json;
    } catch (e) {
        throw new Error(e);
    }
};

const getJwt = async () => {
    try {
        const grantToken = await fetchData(
            `${apiUrl}/api/v1/token`,
            'POST',
            {
                'Content-Type': 'application/json',
            },
            {
                accessKey: settingInfo.accessKey,
                secretKey: settingInfo.secretKey,
            },
        );
        if (grantToken.status === 'success') {
            return grantToken.data.encodedJWT;
        }
        // return null;
        showErrorMessage(`[${grantToken.data.code}] ${grantToken.data.message}`);
    } catch (e) {
        showErrorMessage(e.message);
    }
}

const frequentlyUsedLangs = ['ja', 'en', 'zh-CN', 'zh-TW', 'ko'];
const notProvideLangs = ['zh-HK'];

const setLangsFields = async () => {
    let jwtToken = '';
    try {
        jwtToken = await getJwt();
    } catch (e) {
        showErrorMessage(e.message);
    }

    try {
        const rtnData = await Promise.all([
            new Promise(async (resolve, reject) => {
                const grantFields = await fetchData(
                    `${apiUrl}/api/v1/field/list?engine=t4oo&language=${envInfo.lang === 'ja' ? envInfo.lang : 'en'}`,
                    'GET',
                    {
                        'Content-Type': 'application/json',
                        Authorization: `Bearer ${jwtToken}`,
                    },
                );
                resolve(grantFields);
            }),
            new Promise(async (resolve, reject) => {
                const grantLangs = await fetchData(
                    `${apiUrl}/api/v1/languages/engine/t4oo`,
                    'GET',
                    {
                        'Content-Type': 'application/json',
                        Authorization: `Bearer ${jwtToken}`,
                    },
                );
                resolve(grantLangs);
            }),
        ]);

        $('#rozettaFieldId').html(rtnData.find(o => o.status === 'success' && o.data.fields).data.fields.map(o => `<option value=${o.id}>${o.name}</option>`));
        const langList = rtnData.find(o => o.status === 'success' && o.data.languages).data.languages
            .filter((o) => !notProvideLangs.includes(o.abbreviation))
            .sort((a, b) => {
            if (
                frequentlyUsedLangs.includes(a.abbreviation)
                && frequentlyUsedLangs.includes(b.abbreviation)
            ) {
                return (
                frequentlyUsedLangs.indexOf(a.abbreviation)
                - frequentlyUsedLangs.indexOf(b.abbreviation)
                );
            }
            if (frequentlyUsedLangs.includes(a.abbreviation)) {
                return -1;
            }
            return 1;
            });
        $('#rozettaSourceLang').html(langList.map(o => `<option value=${o.abbreviation}>${o.language}</option>`));
        $('#rozettaTargetLang').html(langList.map(o => `<option value=${o.abbreviation} ${o.abbreviation === 'en' ? 'selected' : ''}>${o.language}</option>`));
    } catch (e) {
        showErrorMessage('Failed to getting languages or fields.');
    }
}

$( function() {
    setLangsFields();
} );

const ignore = {
  STYLE: 0, SCRIPT: 0, NOSCRIPT: 0, OBJECT: 0,
};
const traverseTextNode = (element, callback) => {
  for (const node of element.childNodes) {
    if (node.tagName in ignore) continue;

    switch (node.nodeType) {
      // 1
      case Node.ELEMENT_NODE:
        traverseTextNode(node, callback);
        break;
      // 3
      case Node.TEXT_NODE:
        callback(node);
        break;
      // 9
      case Node.DOCUMENT_NODE:
        traverseTextNode(node, callback);
    }
  }
};

const handleRozettaTranslate = async () => {
    const title = $('#title').val();
    originalTitle = title;
    // newDiv is not shown in the page
    let isVisualMode = $('#content').attr('aria-hidden') || $('#content').attr('aria-hidden') === 'true';
    const newDiv = document.createElement('div');
    originalHtml = isVisualMode ? $('#content_ifr').contents().find('#tinymce').html() || '' : $('#content').val() || '';
    newDiv.innerHTML = originalHtml;
    
    $('#rozetta-translator-error-message').hide();
     $('#rozetta-translator-setting-before').hide();
	 $('#rozetta-translator-status-translating').show();
    
    try {
        const jwtToken = await getJwt();
    
        const originalTextArray = [];
        const extractText = (node) => {
          if (node.nodeValue && node.nodeValue.replace(/[\n\t\r]/g, '').trim() !== '') {
            // console.log(node.textContent);
            node.nodeValue.split('\n').map(o => {
                if (o !== '') {
                    originalTextArray.push(o);
                }
            });
          }
        };
    
        traverseTextNode(newDiv, extractText);
    
        if (envInfo.isACFActive) {
            originalACFText = await getFromACFFields();
        }
    
        const grantText = await fetchData(
            `${apiUrl}/api/v1/translate`,
            'POST',
            {
                'Content-Type': 'application/json',
                Authorization: `Bearer ${jwtToken}`,
            },
            {
                fieldId: $('#rozettaFieldId').val(),
                contractId: settingInfo.contractId,
                sourceLang: $('#rozettaSourceLang').val(),
                targetLang: $('#rozettaTargetLang').val(),
                  text: [title, ...originalTextArray, ...originalACFText],
                autoSplit: false,
                removeFakeLineBreak: false,
            },
        );
        if (grantText.status !== 'success') {
            $('#rozetta-translator-setting-before').show();
            $('#rozetta-translator-status-translating').hide();
            showErrorMessage(`[${grantText.data.code}] ${grantText.data.message}`);
            return;
        }
        
        const translatedArray = grantText.data.translationResult.map(o => o.translatedText);
    
        $('#title').val(translatedArray[0]);
    
        let i = 1;
        const backfillTranslatedText = (node) => {
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
        };
        traverseTextNode(newDiv, (node) => { backfillTranslatedText(node); });
    
        // console.log(translatedArray.slice(i));
        if (envInfo.isACFActive) {
            await setFromACFFields(translatedArray.slice(i));
        }
    
        // console.log(tinyMCE.activeEditor.selection.getContent());
        isVisualMode = $('#content').attr('aria-hidden') || $('#content').attr('aria-hidden') === 'true';
        if (isVisualMode == 'true') {
            $("#content_ifr").contents().find('#tinymce').html(newDiv.innerHTML);
        } else {
            $('#content').val(newDiv.innerHTML);
            $('#content').html(newDiv.innerHTML);
        }
                    
        $('#rozetta-translator-setting-after').show();
        $('#rozetta-translator-status-translating').hide();
    } catch (e) {
        showErrorMessage(e.message);
    }
};

const handleBack = async () => {
    $('#title').val(originalTitle);
    const isVisualMode = $('#content').attr('aria-hidden') || $('#content').attr('aria-hidden') === 'true';
    if (isVisualMode == 'true') {
        $('#content_ifr').contents().find('#tinymce').html(originalHtml);
    } else {
        $('#content').val(originalHtml);
        $('#content').html(originalHtml);
    }
    if (envInfo.isACFActive) {
        await setFromACFFields(originalACFText);
    }
    $('#rozetta-translator-setting-before').show();
    $('#rozetta-translator-setting-after').hide();
}
