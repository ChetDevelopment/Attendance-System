export default {
  plugins: {
    '@tailwindcss/postcss': {},
  },
};
axios.get("http://127.0.0.1:8000/api/dashboard/present-today")
.then(response => {
    this.presentToday = response.data.data
})

axios.get("http://127.0.0.1:8000/api/dashboard/absent-today")
.then(response => {
    this.absentToday = response.data.data
})

axios.get("http://127.0.0.1:8000/api/dashboard/late-today")
.then(response => {
    this.lateToday = response.data.data
})