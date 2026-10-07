// Leaflet for the admin's "Set location on map" picker (loaded only on forms that have one). The picker's Alpine
// component waits for "map-picker:ready" if it renders before this module has loaded.
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'

// The bundler renames Leaflet's marker images, so point the default icon at the bundled ones.
L.Icon.Default.mergeOptions({ iconUrl: markerIcon, iconRetinaUrl: markerIcon2x, shadowUrl: markerShadow })

window.L = L
window.dispatchEvent(new Event('map-picker:ready'))
