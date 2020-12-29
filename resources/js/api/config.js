export const getConfigTips = () => {
    return axios.get(`/config_tips`)
}
export const updateConfigTips = (content) => {
    return axios.post(`/config_tips`, {
        content: content
    })
}
