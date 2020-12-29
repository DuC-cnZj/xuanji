import Vue from 'vue';
import ElementUI from 'element-ui';
import 'element-ui/lib/theme-chalk/index.css';
import DucHighlight from './directive/highlight'

require('./bootstrap');
window.Vue = require('vue');
window.EventBus = new Vue();

Vue.use(DucHighlight)
Vue.use(ElementUI);

Vue.component('my-editor', require('./components/Editor.vue').default);
Vue.component('nav-bar', require('./components/NavBar.vue').default);
Vue.component('app-cards', require('./views/AppCards.vue').default);
Vue.component('shell', require('./components/Shell.vue').default);
Vue.component('md', require('./components/MarkdownEditor.vue').default);

const app = new Vue({
    el: '#app',
});
