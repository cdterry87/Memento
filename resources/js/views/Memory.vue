<template>
    <v-container fluid grid-list-md>
        <v-layout row>
            <v-flex xs10 offset-xs1>
                <div class="headline">
                    {{ memory.title }}
                </div>
                <div class="subheading mt-2">
                    {{ memory.date }}
                </div>
                <div class="mt-4">
                    {{ memory.details }}
                </div>
                <div class="mt-5">
                    <v-divider></v-divider>
                </div>
                <div class="mt-2">
                    <v-system-bar flat color="transparent" height="48" style="padding: 0;">
                        <span>Photos</span>
                        <v-spacer></v-spacer>
                        <v-btn icon small="">
                            <v-icon>mdi-plus</v-icon>
                        </v-btn>
                    </v-system-bar>
                </div>
            </v-flex>
        </v-layout>

        <v-speed-dial class="mb-3 fixed" v-model="fab" :bottom="fabBottom" :right="fabRight" :direction="fabDirection" :transition="fabTransition">
            <template v-slot:activator>
                <v-btn v-model="fab" color="deep-purple" dark fab >
                    <v-icon v-if="fab">mdi-close</v-icon>
                    <v-icon v-else>mdi-dots-horizontal</v-icon>
                </v-btn>
            </template>
            <v-btn fab dark small color="red accent-4" @click="deleteMemory"><v-icon>mdi-trash-can</v-icon></v-btn>
            <v-btn fab dark small color="deep-purple"><v-icon>mdi-camera</v-icon></v-btn>
            <v-btn fab dark small color="deep-purple"><v-icon>mdi-square-edit-outline</v-icon></v-btn>
        </v-speed-dial>
    </v-container>
</template>

<script>
    export default {
        name: 'Memory',
        props: ['id'],
        data() {
            return {
                fab: false,
                fabBottom: true,
                fabRight: true,
                fabDirection: 'left',
                fabTransition: 'slide-y-reverse-transition',
                loading: true,
                emotions: [],
                reasons: [],
                memory: ''
            }
        },
        methods: {
            getEmotions() {
                axios.get('/api/emotions')
                .then(response => {
                    this.emotions = response.data
                })
            },
            getReasons() {
                axios.get('/api/reasons')
                .then(response => {
                    this.reasons = response.data
                })
            },
            getMemory() {
                axios.get('/api/memories/' + this.id)
                .then(response => {
                    this.memory = response.data

                    this.loading = false
                })
            },
            deleteMemory() {
                axios.delete('/api/memories/' + this.id)
                .then(response => {
                    this.$router.push('/home')
                })
            },
        },
        created () {
            this.getEmotions()
            this.getReasons()
            this.getMemory()
        },
    }
</script>
