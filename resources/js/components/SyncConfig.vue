<template>
    <el-alert style="margin-bottom: 10px;" type="info">
        Tips: 如果 .xuanji.yaml 文件发生改动，请执行同步按钮
        <el-button
            type="info"
            icon="el-icon-refresh-right"
            style="padding: 5px;"
            circle
            :loading="loading"
            @click="sync"
        ></el-button>
    </el-alert>
</template>

<script>
import { sync } from "../api/gitlab";

export default {
    data() {
        return {
            loading: false
        };
    },
    methods: {
        sync() {
            this.loading = true;
                sync().then(({data})=>{
                    let title = `项目同步成功, 共 ${data.imported} 个`
                    if (data.invalid) {
                        title += `， 失败的为 ${data.invalid}`
                    }
                    this.$notify({
                        title: title,
                        type: "success",
                        position: "top-left"
                    });
                    this.loading = false;
                }).catch(e => {
                    this.loading = false;
                })
        }
    }
};
</script>
