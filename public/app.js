import { createApp } from "https://unpkg.com/vue@3/dist/vue.esm-browser.prod.js";
import * as leaflet from 'https://cdn.skypack.dev/leaflet'
import makeVisible from "./observer.js";

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
            category: null,
            thinking: false,
            error: false,
            suggestions: null,
        };
    },
    watch: {
        visibility: {
            handler() {
                makeVisible("#map-tile");
            },
            immediate: true,
        },
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
        fixDate(date) {
            // I hate JavaScript so much it's unreal
            return new Date(date).valueOf();
        },
        setLatLongSize(latitude, longitude, size) {
            this.latitude = latitude;
            this.longitude = longitude;
            this.size = size;
            fireZone.setLatLng([this.latitude, this.longitude]);
            fireZone.setRadius(this.acreRadius(this.size));
            map.fitBounds(fireZone.getBounds());
            map.flyTo([this.latitude, this.longitude]);
        },
        setDates(startDate, endDate) {
            this.startDate = new Date(startDate);
            this.endDate = new Date(endDate);
        },
        setFireData(latitude, longitude, size, startDate, endDate) {
            this.setLatLongSize(latitude, longitude, size);
            this.setDates(startDate, endDate);
        },
        getPrediction() {
            this.cause = null;
            this.error = false;
            this.thinking = true;
            const attrs = {
                "latitude": this.latitude,
                "longitude": this.longitude,
                "size": this.size,
                "startDate": new Date(this.startDate).getTime(),
                "endDate": new Date(this.endDate).getTime(),
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
                    console.log(data);
                    this.thinking = false;
                    this.cause = data.prediction;
                    this.category = data.category;
                })
                .catch((e) => {
                    this.error = true; 
                    console.log(e)
                });
        },
        fetchFire(fireId) {
            fetch("/api.php?fireId=" + fireId)
                .then((res) => res.json())
                .then((data) => {
                    this.setLatLongSize(data.latitude, data.longitude, data.size);
                })
        },
        fetchSuggestions() {
            fetch("/api.php?suggest=1")
                .then((res) => res.json())
                .then((data) => {
                    this.suggestions = data;
                })
        },
    },
    mounted() {
        this.constructMap();
        this.fetchSuggestions();
    },
});

mapTile.mount("#map-tile");

const allCausesGraph = createApp({
    data() {
        return {
            causeKV: {},
        };
    },
    watch: {
        visibility: {
            handler() {
                makeVisible("#all-causes-tile");
            },
            immediate: true,
        },
    },
    methods: {
        fetchKV() {
            fetch("/api.php?analysis=allCauses")
                .then((res) => res.json())
                .then((data) => {
                    this.causeKV = data;
                });
        },
    },
    mounted() {
        this.fetchKV();
    },
});

allCausesGraph.mount("#all-causes-graph");

const annualDataTile = createApp({
    data() {
        return {
            annualData: {},
            yearStats: {
                "causes": {
                    "cause": "Hover over a year",
                    "pct": 100,
                },
            },
            focusedSource: "Hover over/tap an element",
        };
    },
    watch: {
        visibility: {
            handler() {
                makeVisible("#annual-data-tile");
            },
            immediate: true,
        },
    },
    methods: {
        fetchKV() {
            fetch("/api.php?analysis=countByYear")
                .then((res) => res.json())
                .then((data) => {
                    data.years.sort((a, b) => {
                        if (a.year > b.year) {
                            return 1;
                        }
                        if (b.year > a.year) {
                            return -1;
                        }
                        return 0;
                    });
                    this.annualData = data;
                });
        },
        setYearStats(yearStats) {
            this.yearStats = yearStats;
            this.yearStats.causes.sort((a, b) => {
                if (a.cause > b.cause) {
                    return 1;
                }
                if (b.cause > a.cause) {
                    return -1;
                }
                return 0;
            })
        },
        getBarColor(causeDesc) {
            switch (causeDesc) {
                case "Lightning":
                    return "var(--spanish-blue)";
                case "Debris Burning":
                    return "var(--cafe-au-lait)";
                case "Arson":
                    return "var(--scarlet)";
                case "Campfire":
                    return "var(--forest-green)";
                case "Equipment Use":
                    return "var(--russian-violet)";
                case "Smoking":
                    return "var(--smoky-black)";
                case "Fireworks":
                    return "var(--cherry)";
                case "Railroad":
                    return "var(--cafe-noir)";
                case "Powerline":
                    return "var(--oxford-blue)";
                case "Structure":
                    return "var(--gunmetal)";
                default:
                    return "var(--linen)";
            }
        },
        setFocusedSource(cause, count) {
            this.focusedSource = cause + ": " + count + " fires";
        }
    },
    mounted() {
        this.fetchKV();
    },
});

annualDataTile.mount("#annual-data-tile");

const dayOfYearTile= createApp({
    data() {
        return {
            doyData: null,
            focusedDay: "Hover over/tap a day of the year", 
        };
    },
    methods: {
        fetchKV() {
            fetch("/api.php?analysis=countByDoy")
                .then((res) => res.json())
                .then((data) => {
                    this.doyData = data;
                });
        },
        setFocusedDay(day) {
            this.focusedDay = `${day.datestamp}: ${day.count} fires, 1992-2015`;
        }
    },
    watch: {
        visibility: {
            handler() {
                makeVisible("#day-of-year-tile");
            },
            immediate: true,
        },
    },
    mounted() {
        this.fetchKV();
    },
});

dayOfYearTile.mount("#day-of-year-tile");

const gridMapTile = createApp({
    data() {
        return {
            // fml
            coords: new Map([
                ["AK", [0, 0]],["ME", [0, 10]],["WI", [1, 5]],["VT", [1, 9]],
                ["NH", [1, 10]],["WA", [2, 0]],["ID", [2, 1]],["MT", [2, 2]],
                ["ND", [2, 3]],["MN", [2, 4]],["IL", [2, 5]],["MI", [2, 6]],
                ["NY", [2, 8]],["MA", [2, 9]],["OR", [3, 0]],["NV", [3, 1]],
                ["WY", [3, 2]],["SD", [3, 3]],["IA", [3, 4]],["IN", [3, 5]],
                ["OH", [3, 6]],["PA", [3, 7]],["NJ", [3, 8]],["CT", [3, 9]],
                ["RI", [3, 10]],["CA", [4, 0]],["UT", [4, 1]],["CO", [4, 2]],
                ["NE", [4, 3]],["MO", [4, 4]],["KY", [4, 5]],["WV", [4, 6]],
                ["VA", [4, 7]],["MD", [4, 8]],["DE", [4, 9]],["AZ", [5, 1]],
                ["NM", [5, 2]],["KS", [5, 3]],["AR", [5, 4]],["TN", [5, 5]],
                ["NC", [5, 6]],["SC", [5, 7]],["DC", [5, 8]],["OK", [6, 3]],
                ["LA", [6, 4]],["MS", [6, 5]],["AL", [6, 6]],["GA", [6, 7]],
                ["HI", [7, 0]],["TX", [7, 3]],["FL", [7, 8]],["PR", [7, 10]]
            ]),
            palette: [
                {"pct": 0, "color": "#009900"},
                {"pct": 1, "color": "#1E8406"},
                {"pct": 2, "color": "#3C6E0D"},
                {"pct": 4, "color": "#5A5913"},
                {"pct": 8, "color": "#78441A"},
                {"pct": 16, "color": "#962F20"},
                {"pct": 24, "color": "#B41927"},
                {"pct": 32, "color": "#D2042D"},
            ],
            stateData: {},
            focusedState: "Hover over/tap a state",
        };
    },
    methods: {
        fetchStateData() {
            fetch("/api.php?analysis=countByState")
                .then((res) => res.json())
                .then((data) => {
                    this.stateData = data;
                });
        },
        getStyle(entry) {
            let color;
            this.palette.forEach((swatch) => {
                if (entry.pct >= swatch.pct) color = swatch.color;
            });
            const rowColumn = this.coords.get(entry.state);
            return {
                "grid-row": rowColumn[0] + 1,
                "grid-column": rowColumn[1] + 1,
                "background-color": color
            };
        },
        setFocusedState(entry) {
            this.focusedState = `${entry.state}: ${entry.count} fires, ${entry.acresBurned} acres burned, 1992-2015`;
        },
    },
    watch: {
        visibility: {
            handler() {
                makeVisible("#grid-map-tile");
            },
            immediate: true,
        },
    },
    mounted() {
        this.fetchStateData();
    },
});

gridMapTile.mount("#grid-map-tile");

makeVisible("#sources-tile");
