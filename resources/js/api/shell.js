import axios from "axios"

let ajax = axios.create({
    baseURL: window.MIX_SHELL_SOCKET_URL
})
console.log((window.MIX_SHELL_SOCKET_URL))

export const handleExec = (obj) => {
    const {namespace, pod, container = ""} = obj

    return ajax.get(`pod/${window.MIX_NS_PREFIX}${namespace}/${pod}/shell?container=${container}`)
}

export default {
    handleExec
}
