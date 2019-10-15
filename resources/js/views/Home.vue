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
                        <v-list subheader>
                            <v-list-item>
                                <v-list-item-content>
                                    <v-list-item-title>Add a page to your story!</v-list-item-title>
                                </v-list-item-content>
                            </v-list-item>
                        </v-list>
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
