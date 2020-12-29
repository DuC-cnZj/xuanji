<template>
    <div class="nav">
        <div class="container">
            <div class="left">
                <ul>
                    <li>{{appName}}</li>
                </ul>
            </div>

            <div class="right">
                <ul>
                    <li>
                        <el-dropdown>
                          <span class="el-dropdown-link">
                            {{userName}}<i class="el-icon-arrow-down el-icon--right"></i>
                          </span>
                            <el-dropdown-menu slot="dropdown">
                                <el-dropdown-item command="logout">
                                    <a href="/logout" @click.prevent="logout" class="logout">
                                        登出
                                    </a>
                                    <form action="/logout" method="post" id="logout-form">
                                        <input type="hidden" name="_token" :value="csrf">
                                    </form>
                                </el-dropdown-item>
                            </el-dropdown-menu>
                        </el-dropdown>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: ['appName', 'userName', 'csrf'],
        name: "NavBar",
        methods: {
            logout() {
                document.getElementById("logout-form").submit()
            }
        }
    }
</script>

<style scoped>
.nav {
    border-radius: 3px;
    width: 100%;
    height: 50px;
    line-height: 50px;
    background: #a29bfe;
    box-shadow: 0px 4px 4px gray;
}

.container{
    max-width: 1280px;
    margin: 0 auto;
    height: 100%;
}

.left > ul {
    float: left;
}

.left > ul > li {
    float: left;
    margin-right: 10px;
}

.right > ul {
    float: right;
}

.right > ul > li {
    float: left;
    margin-right: 10px;
}

.el-dropdown-link {
    cursor: pointer;
    color: black;
}
.el-icon-arrow-down {
    font-size: 12px;
}

.logout {
    text-decoration: none;
}
</style>
