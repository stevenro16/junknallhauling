// Vanilla Leaflet job-locations map, ported from the Next.js InquiryMapInner
// component. Exposes window.HaulMap.init(el) → Promise<{ setInquiries(list) }>.
// Leaflet is loaded on demand (dynamic import) so it stays out of the public
// bundle — only admin pages with a map fetch its chunk.

const CENTER = [34.05, -117.18];
const ZOOM = 10;

// Approximate centers for key Inland Empire zips served by Junk N All Hauling.
const ZIP_APPROX = {
    '92399': [34.033, -117.043], '92373': [34.055, -117.182], '92374': [34.065, -117.150],
    '92346': [34.128, -117.208], '92320': [34.000, -117.060], '92335': [34.090, -117.435],
    '92336': [34.120, -117.400], '92401': [34.105, -117.290], '92404': [34.140, -117.250],
    '92307': [34.480, -117.240],
};

function hashToOffset(seed, scale) {
    const str = String(seed);
    let h = 2166136261;
    for (let i = 0; i < str.length; i++) { h ^= str.charCodeAt(i); h = Math.imul(h, 16777619); }
    return ((h % 2000) / 1000 - 1) * scale;
}

function getApproxPosition(inq) {
    let baseLat = CENTER[0], baseLng = CENTER[1];
    if (inq.zip_code) {
        const zip = inq.zip_code.trim();
        const known = ZIP_APPROX[zip] || ZIP_APPROX[zip.slice(0, 5)];
        if (known) { [baseLat, baseLng] = known; }
        else {
            const zipTail = parseInt(zip.slice(-3)) || 0;
            baseLat += ((zipTail % 11) - 5) * 0.012;
            baseLng += ((zipTail % 13) - 6) * 0.015;
        }
    } else if (inq.address) {
        const addr = inq.address.toLowerCase();
        if (addr.includes('yucaipa')) [baseLat, baseLng] = ZIP_APPROX['92399'];
        else if (addr.includes('redlands')) [baseLat, baseLng] = ZIP_APPROX['92373'];
        else if (addr.includes('highland')) [baseLat, baseLng] = ZIP_APPROX['92346'];
        else if (addr.includes('calimesa') || addr.includes('cherry valley')) [baseLat, baseLng] = ZIP_APPROX['92320'];
        else if (addr.includes('san bernardino') || addr.includes('sb')) [baseLat, baseLng] = ZIP_APPROX['92401'];
    }
    const stableSeed = inq.ref || inq.id || '';
    let addrJitterLat = 0, addrJitterLng = 0;
    if (inq.address) {
        const addr = inq.address.replace(/[^0-9A-Za-z\s]/g, ' ').trim();
        const match = addr.match(/\d+/);
        const streetNum = match ? match[0] : addr.slice(-6);
        addrJitterLat = hashToOffset(streetNum + stableSeed, 0.0045);
        addrJitterLng = hashToOffset(stableSeed + streetNum, 0.0055);
    }
    const microLat = hashToOffset(stableSeed, 0.0018);
    const microLng = hashToOffset(stableSeed + 'x', 0.0022);
    return [baseLat + addrJitterLat + microLat, baseLng + addrJitterLng + microLng];
}

function getPosition(inq) {
    if (inq.latitude != null && inq.longitude != null) return [inq.latitude, inq.longitude];
    return getApproxPosition(inq);
}

function popupHtml(inq) {
    const esc = (s) => String(s ?? '').replace(/</g, '&lt;');
    let html = `<div class="text-sm" style="min-width:200px">`;
    html += `<div style="font-family:monospace;font-size:11px;color:#CA8A04;margin-bottom:2px">${esc(inq.ref)}</div>`;
    html += `<div style="font-weight:600">${esc(inq.name)}</div>`;
    html += `<div style="color:#9ca3af;font-size:11px;margin-top:2px">${esc(inq.address || inq.zip_code || 'No address')}</div>`;
    html += `<div style="margin-top:6px;font-size:11px"><span style="color:#9ca3af">Status:</span> <span style="font-weight:500">${esc(inq.status)}</span></div>`;
    if (inq.equipment_type) html += `<div style="font-size:11px;margin-top:2px;color:#CA8A04">${esc(inq.equipment_type)}</div>`;
    if (inq.quoted_price) html += `<div style="font-size:11px;margin-top:2px"><span style="color:#9ca3af">Quoted:</span> <span style="color:#059669;font-weight:500">$${esc(inq.quoted_price)}</span></div>`;
    if (inq.payment_method) html += `<div style="font-size:11px;margin-top:2px"><span style="color:#9ca3af">Paid via:</span> <span style="color:#059669;font-weight:500">${esc(inq.payment_method)}</span></div>`;
    html += `<a href="${window.appBaseUrl}/admin/inquiries/${esc(inq.id)}" target="_blank" style="display:inline-block;margin-top:6px;color:#CA8A04;font-size:11px">View details &rarr;</a>`;
    html += `</div>`;
    return html;
}

const ACTIVE_STATUSES = ['scheduled', 'service_performed', 'quoted', 'reviewing', 'new'];

window.HaulMap = {
    async init(el) {
        const [{ default: L }] = await Promise.all([
            import('leaflet'),
            import('leaflet/dist/leaflet.css'),
        ]);
        const map = L.map(el).setView(CENTER, ZOOM);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(map);
        const icon = L.icon({
            iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
            iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
            iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41],
        });
        const layer = L.layerGroup().addTo(map);
        return {
            map,
            setInquiries(list) {
                layer.clearLayers();
                (list || []).filter((i) => ACTIVE_STATUSES.includes(i.status)).forEach((inq) => {
                    L.marker(getPosition(inq), { icon }).bindPopup(popupHtml(inq)).addTo(layer);
                });
                setTimeout(() => map.invalidateSize(), 60);
            },
        };
    },
};
