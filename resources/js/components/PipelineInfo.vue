<template>
    <div v-if="showPipeline">
        <el-alert
            style="font-size: 12px;margin-bottom: 10px"
            :type="pipelineVars[status].alertType"
            :title="pipelineVars[status].alertTitle"
            show-icon
        >
            <el-link
                target="_blank"
                style="font-size: 12px"
                :type="pipelineVars[status].linkType"
                :href="webUrl"
            >点击查看 pipeline 详细信息
            </el-link>
        </el-alert>
    </div>
</template>

<script>
import {pipeline} from "../api/gitlab";

export default {
    name: "PiplineInfo",
    props: ['branch', 'project', 'commit'],
    data() {
        return {
            status: "",
            webUrl: "",
            pipelineInfo: [],
            pipelineVars: {
                failed: {
                    alertType: "error",
                    alertTitle: "pipeline 执行失败",
                    linkType: "danger"
                },
                success: {
                    alertType: "success",
                    alertTitle: "pipeline 执行成功",
                    linkType: "success"
                },
                running: {
                    alertType: "warning",
                    alertTitle: "pipeline 还在执行中",
                    linkType: "warning"
                }
            },
        }
    },
    created() {
        this.fetchPipelineInfo()
    },
    computed: {
        showPipeline() {
            if (this.pipelineInfo.length > 0) {
                if (Object.keys(this.pipelineVars).includes(this.status)) {
                    return true;
                }
            }

            return false;
        }
    },
    methods: {
        async fetchPipelineInfo() {
            if (!this.commit) {
                return
            }
            const { data } = await pipeline(
                this.project,
                this.branch,
                this.commit,
            );
            this.pipelineInfo = data;
            if (this.pipelineInfo.length > 0) {
                this.webUrl = data['0']['web_url']
                this.status = data['0']['status']
            }
        }
    },
    watch: {
        commit() {
            this.fetchPipelineInfo()
        },
    }
}
</script>
