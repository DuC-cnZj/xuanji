window._ = require('lodash');
import axios from "axios"
import { Message, Notification } from 'element-ui';

try {
    window.Popper = require('popper.js').default;
    window.$ = window.jQuery = require('jquery');

    require('bootstrap');
} catch (e) {}


// 添加请求拦截器
axios.interceptors.request.use(function (config) {
    // 在发送请求之前做些什么
    return config;
}, function (error) {
    // 对请求错误做些什么
    return Promise.reject(error);
});

// 添加响应拦截器
axios.interceptors.response.use(function (response) {
    // 对响应数据做点什么
    return response;
}, function (error) {
    if (error.response.status >= 500) {
        Notification({
            type: 'error',
            customClass: "error-notify",
            title: "Whoops!",
            dangerouslyUseHTMLString: true,
            message: error.response.data.msg,
            duration: 0,
        })
    } else if (error.response.status === 422) {
        for (let field in error.response.data.errors) {
            error.response.data.errors[field].forEach((msg)=>{
                Notification({
                    type: 'error',
                    title: field,
                    message: msg,
                    duration: 3000,
                })
            })
        }
    } else {
        Notification({
            type: 'error',
            title: error.response.status,
            message: error.response.data.message,
            duration: 3000,
        })
    }

    // 对响应错误做点什么
    return Promise.reject(error);
});

window.axios = axios
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
