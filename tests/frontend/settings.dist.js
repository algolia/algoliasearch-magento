var process = require('process');

module.exports = {
    baseUrl: process.env.BASE_URL || 'http://localhost',
    services: ['phantomjs'], // 'selenium-standalone'
    browserName: 'chrome'
};