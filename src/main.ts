import { createApp } from 'vue'
import './style.css'
import "./scss/VMessage.scss"
import App from './App.vue'
import ROUTER from "./router"
import VMessage from "./component/Message.ts"


const app = createApp(App);
app.use(ROUTER)
app.use(VMessage)
app.mount('#root')
