var execFileSync = require('child_process').execFileSync;

module.exports = function initBrowserCommands(browser) {
    browser.addCommand('updateConfig', function (param) {
        var stdout = execFileSync('php', [
            __dirname+'/config/update-config.php',
            param
        ]);
        return stdout.toString();
    })
};