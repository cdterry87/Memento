<template>
   <v-container fluid grid-list-md>
        <v-layout row>
            <v-flex xs12>
                <v-list dark color="transparent" shaped two-line>
                    <v-subheader class="title">
                        <span>My Story</span>
                    </v-subheader>
                    <v-list-item-group color="deep-purple">
                        <v-list-item v-for="(memory, i) in memories" :key="i">
                            <v-list-item-icon>
                                <v-icon v-text="memory.icon"></v-icon>
                            </v-list-item-icon>
                            <v-list-item-content>
                                <v-list-item-title v-text="memory.title"></v-list-item-title>
                                <v-list-item-subtitle v-text="memory.date"></v-list-item-subtitle>
                            </v-list-item-content>
                        </v-list-item>
                    </v-list-item-group>
                </v-list>
                <v-dialog v-model="dialog" fullscreen hide-overlay transition="dialog-bottom-transition">
                    <template v-slot:activator="{ on }">
                        <v-btn
                            class="fixed"
                            color="deep-purple"
                            dark
                            large
                            absolute
                            bottom
                            right
                            fab
                            @click="dialog = true"
                            style="margin-bottom: 50px;"
                        >
                            <v-icon>mdi-plus</v-icon>
                        </v-btn>
                    </template>
                    <v-card light>
                        <v-toolbar color="transparent" flat>
                            <v-spacer></v-spacer>
                            <v-btn icon @click="dialog = false">
                                <v-icon>mdi-close</v-icon>
                            </v-btn>
                        </v-toolbar>
                        <div class="text-center">
                            <div v-if="panel == 1">
                                <div class="body-2 mb-2">
                                    Add a new page to your story!
                                </div>

                                <v-date-picker color="deep-purple" v-model="date"></v-date-picker>

                                <v-footer absolute color="transparent" class="text-center mb-5">
                                    <v-spacer></v-spacer>
                                    <v-btn outlined rounded large color="teal accent-4" dark @click="panel = 2">Next <v-icon>mdi-arrow-right-thick</v-icon></v-btn>
                                    <v-spacer></v-spacer>
                                </v-footer>
                            </div>
                            <div v-if="panel == 2">
                                <div class="body-2 mb-2">
                                    So, how was your day?
                                </div>

                                <v-icon color="deep-purple" size="225" v-show="emotion == e.level" v-for="(e, index) in emotions" :key="index">{{ e.icon }}</v-icon>

                                <v-container grid-list-md>
                                    <v-layout row>
                                        <v-flex xs10 offset-xs1>
                                            <v-slider v-model="emotion" min="1" max="11" color="deep-purple" track-color="teal accent-4"></v-slider>
                                        </v-flex>
                                    </v-layout>
                                </v-container>

                                <v-footer absolute color="transparent" class="text-center mb-5">
                                    <v-spacer></v-spacer>
                                    <v-btn class="ma-1" outlined rounded large color="deep-purple" dark @click="panel = 1"><v-icon>mdi-arrow-left-thick</v-icon> Back</v-btn>
                                    <v-btn class="ma-1" outlined rounded large color="teal accent-4" dark @click="panel = 3">Next <v-icon>mdi-arrow-right-thick</v-icon></v-btn>
                                    <v-spacer></v-spacer>
                                </v-footer>
                            </div>
                            <div v-if="panel == 3">
                                <div class="body-2 mb-2">
                                    What made your day so _?
                                </div>

                                <v-container>
                                    <v-layout row wrap>
                                        <v-flex xs12>
                                            <v-list class="text-left">
                                                <v-list-item v-for="(reason, index) in reasons" :key="index" @click="reason.selected = !reason.selected">
                                                    <v-list-item-action>
                                                        <v-icon color="deep-purple">{{ reason.icon }}</v-icon>
                                                    </v-list-item-action>
                                                    <v-list-item-content>
                                                        <v-list-item-title>{{ reason.title }}</v-list-item-title>
                                                    </v-list-item-content>
                                                    <v-list-item-action>
                                                        <v-checkbox
                                                            color="teal accent-4"
                                                            :value="reason.selected"
                                                            v-model="reason[index]"
                                                        ></v-checkbox>
                                                    </v-list-item-action>
                                                </v-list-item>
                                            </v-list>
                                        </v-flex>
                                    </v-layout>
                                </v-container>

                                <v-footer absolute color="transparent" class="text-center mb-5">
                                    <v-spacer></v-spacer>
                                    <v-btn class="ma-1" outlined rounded large color="deep-purple" dark @click="panel = 2"><v-icon>mdi-arrow-left-thick</v-icon> Back</v-btn>
                                    <v-btn class="ma-1" outlined rounded large color="teal accent-4" dark @click="panel = 4">Next <v-icon>mdi-arrow-right-thick</v-icon></v-btn>
                                    <v-spacer></v-spacer>
                                </v-footer>
                            </div>
                            <div v-if="panel == 4">
                                <div class="body-2 mb-2">
                                    Would you like to add any details?
                                </div>

                                <v-container grid-list-md>
                                <v-layout row wrap>
                                    <v-flex xs12>
                                        <v-textarea
                                        v-model="details"
                                        outlined
                                        name="details"
                                        label="Details"
                                        color="teal accent-4"
                                        rows="15"
                                        ></v-textarea>
                                    </v-flex>
                                </v-layout>
                                </v-container>

                                <v-footer absolute color="transparent" class="text-center mb-5">
                                    <v-spacer></v-spacer>
                                    <v-btn class="ma-1" outlined rounded large color="deep-purple" dark @click="panel = 3"><v-icon>mdi-arrow-left-thick</v-icon> Back</v-btn>
                                    <v-btn class="ma-1" outlined rounded large color="teal accent-4" dark>Done <v-icon>mdi-check-circle</v-icon></v-btn>
                                    <v-spacer></v-spacer>
                                </v-footer>
                            </div>
                        </div>
                    </v-card>
                </v-dialog>
            </v-flex>
        </v-layout>
   </v-container>
</template>

<script>
    export default {
        name: 'Home',
        data() {
            return {
                dialog: false,
                panel: 1,
                date: new Date().toISOString().substr(0, 10),
                emotion: 6,
                details: '',
                emotions: [
                    { emotion: 1, icon: 'mdi-emoticon-dead-outline' },
                    { emotion: 2, icon: 'mdi-emoticon-cry-outline' },
                    { emotion: 3, icon: 'mdi-emoticon-sad-outline' },
                    { emotion: 4, icon: 'mdi-emoticon-angry-outline' },
                    { emotion: 5, icon: 'mdi-emoticon-neutral-outline' },
                    { emotion: 6, icon: 'mdi-emoticon-happy-outline' },
                    { emotion: 7, icon: 'mdi-emoticon-outline' },
                    { emotion: 8, icon: 'mdi-emoticon-tongue-outline' },
                    { emotion: 9, icon: 'mdi-emoticon-cool-outline' },
                    { emotion: 10, icon: 'mdi-emoticon-kiss-outline' },
                    { emotion: 11, icon: 'mdi-emoticon-excited-outline' },
                ],
                reasons: [
                    { reason: 'Work', icon: 'mdi-email', selected: false},
                    { reason: 'Family', icon: 'mdi-home', selected: false},
                    { reason: 'Relationship', icon: 'mdi-heart-multiple', selected: false},
                    { reason: 'Friends', icon: 'mdi-account-group', selected: false},
                    { reason: 'School', icon: 'mdi-school', selected: false},
                    { reason: 'Travel', icon: 'mdi-earth', selected: false},
                    { reason: 'Other', icon: 'mdi-plus', selected: false},
                ],
                memories: [
                    { title: 'Sister\'s Wedding', date: 'June 22nd, 2019', icon: 'mdi-emoticon-cry' },
                    { title: 'Date Night!', date: 'June 30th, 2019', icon: 'mdi-emoticon-kiss' },
                    { title: 'Geek Convention', date: 'July 13th, 2019', icon: 'mdi-emoticon-cool' },
                    { title: 'Work Trip', date: 'July 15th, 2019', icon: 'mdi-emoticon-angry' },
                    { title: 'Rock Show', date: 'September 22nd, 2019', icon: 'mdi-emoticon-cool' },
                    { title: 'My Awesome Birthday Party', date: 'September 25th, 2019', icon: 'mdi-emoticon' },
                    { title: 'Out With Friends', date: 'September 28th, 2019', icon: 'mdi-emoticon-tongue' },
                    { title: 'The Water Lantern Festival', date: 'October 5th, 2019', icon: 'mdi-emoticon-excited' },
                    { title: 'Halloween Party', date: 'October 31st, 2019', icon: 'mdi-emoticon-devil' },
                ],
            }
        },
    }
</script>

<style lang="scss" scoped>

</style>
