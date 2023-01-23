import { createApp } from "https://unpkg.com/vue@3/dist/vue.esm-browser.js";
import * as leaflet from 'https://cdn.skypack.dev/leaflet'

let map;
let fireZone;

const mapTile = createApp({
    data() {
        return {
            latitude: 45.5,
            longitude: -122.5,
            zoom: 10,
            size: 0,
            startDate: 0,
            endDate: 0,
            fireId: null,
            cause: null,
            thinking: false,
        };
    },
    methods: {
        acreRadius(acres) {
            const sqMeters = acres * 4046.86;
            return (Math.sqrt(sqMeters / 3.14));
        },
        constructMap() {
            map = L.map('map').setView([this.latitude, this.longitude], this.zoom);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);
            fireZone = (L.circle([this.latitude, this.longitude], {
                color: "red",
                fillColor: "#E83D0E",
                fillOpacity: 0.5,
                radius: 0,
            }).addTo(map));
        },
        setData(latitude, longitude, size, startDate, endDate) {
            this.latitude = latitude;
            this.longitude = longitude;
            this.size = size;
            this.startDate = startDate;
            this.endDate = endDate;
            map.flyTo([this.latitude, this.longitude]);
            fireZone.setLatLng([this.latitude, this.longitude]);
            fireZone.setRadius(this.acreRadius(this.size));
            map.fitBounds(fireZone.getBounds());
        },
        getPrediction(latitude, longitude, size, startDate, endDate) {
            this.setData(latitude, longitude, size, startDate, endDate);
            this.cause = null;
            this.thinking = true;
            const attrs = {
                "latitude": this.latitude,
                "longitude": this.longitude,
                "size": this.size,
                "startDate": new Date(this.startDate).toDateString(),
                "endDate": new Date(this.endDate).toDateString(),
            };
            fetch("/api.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(attrs),
            })
                .then((res) => res.json())
                .then((data) => {
                    this.thinking = false;
                    console.log(data)
                    this.cause = data.prediction;
                })
                .catch((e) => console.error(e));
        },
        fetchFire(fireId) {
            fetch("/api.php?fireId=" + fireId)
                .then((res) => res.json())
                .then((data) => {
                    console.log(data);
                    this.setData(data.latitude, data.longitude, data.size, data.discoveryDate, data.containmentDate);
                })
                .catch((e) => console.error(e));
        },
    },
    mounted() {
        this.constructMap();
    },
});

mapTile.mount("#map-tile");


