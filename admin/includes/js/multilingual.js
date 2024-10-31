const $ = jQuery;
const apiUrl = 'https://translate.rozetta-api.io';
let originalTitle = '';
let originalHtml = '';

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
    return null;
}

const frequentlyUsedLangs = ['ja', 'en', 'zh-CN', 'zh-TW', 'ko'];
const notProvideLangs = ['zh-HK'];

const setLangs = async () => {
    const jwtToken = await getJwt();
    const grantLangs = await fetchData(
        `${apiUrl}/api/v1/languages/engine/t4oo`,
        'GET',
        {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${jwtToken}`,
        },
    );

    try {
        if (grantLangs.status !== 'success') {
            throw new Error('err');
        }
        const langsList = grantLangs.data.languages;
        $('#rozettaSettingLang').html(langsList
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
            })
            .map(o => `<option value=${o.abbreviation}>${o.language}</option>`)
        );
    } catch (e) {
        alert('Grant Langs failed');
    }
}

$(() => {
    // TODO handle different contract
    // setLangs();
});
