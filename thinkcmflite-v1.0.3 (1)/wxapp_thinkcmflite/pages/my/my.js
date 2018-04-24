var api = require('../../utils/api.js')

Page({
    data: {
        list: [],
    },
    onLoad() {

        wx.getStorage({
            key: 'user',
            success: (res) => {
                this.setData({user: res.data});
            }
        })


    }
});