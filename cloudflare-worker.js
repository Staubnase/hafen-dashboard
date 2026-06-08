const TARGETS = {
  bsh:        'https://gdi.bsh.de/ldproxy/rest/services/WaterLevelForecast/collections/waterlevelforecastdata/items/hamburg_st-pauli?f=json',
  mobilithek: 'https://mobilithek.info:8443/mobilithek/api/v1.0/subscription/981881661821800448/clientPullService?subscriptionID=981881661821800448',
};

export default {
  async fetch(request) {
    const url = new URL(request.url);
    const target = url.searchParams.get('target');

    if (!target || !TARGETS[target]) {
      return new Response('Unbekanntes Ziel', { status: 400 });
    }

    const upstream = await fetch(TARGETS[target], {
      headers: {
        'User-Agent': 'Mozilla/5.0',
        'Accept': 'application/json, text/xml, */*',
      },
    });

    const body = await upstream.arrayBuffer();

    return new Response(body, {
      status: upstream.status,
      headers: {
        'Content-Type': upstream.headers.get('Content-Type') || 'application/octet-stream',
        'Access-Control-Allow-Origin': '*',
        'Cache-Control': 'max-age=120',
      },
    });
  },
};
