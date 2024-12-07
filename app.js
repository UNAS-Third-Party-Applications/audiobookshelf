/**
 * Audiobookshelf App
 * Defined an App to manage audiobookshelf
 */
var AudiobookshelfApp = AudiobookshelfApp || {} //Define audiobookshelf App namespace.
/**
 * Constructor UNAS App
 */
AudiobookshelfApp.App = function () {
  this.id = 'Audiobookshelf'
  this.name = 'Audiobookshelf'
  this.version = '6.0.4'
  this.active = false
  this.menuIcon = '/apps/audiobookshelf/images/logo.png?v=6.0.4&'
  this.shortcutIcon = '/apps/audiobookshelf/images/logo.png?v=6.0.4&'
  this.entryUrl = '/apps/audiobookshelf/index.html?v=6.0.4&'
  var self = this
  this.AudiobookshelfAppWindow = function () {
    if (UNAS.CheckAppState('Audiobookshelf')) {
      return false
    }
    self.window = new MUI.Window({
      id: 'AudiobookshelfAppWindow',
      title: UNAS._('Audiobookshelf'),
      icon: '/apps/audiobookshelf/images/logo_small.png?v=6.0.4&',
      loadMethod: 'xhr',
      width: 750,
      height: 480,
      maximizable: false,
      resizable: true,
      scrollbars: false,
      resizeLimit: { x: [200, 2000], y: [150, 1500] },
      contentURL: '/apps/audiobookshelf/index.html?v=6.0.4&',
      require: { css: ['/apps/audiobookshelf/css/index.css'] },
      onBeforeBuild: function () {
        UNAS.SetAppOpenedWindow('Audiobookshelf', 'AudiobookshelfAppWindow')
      },
    })
  }
  this.AudiobookshelfUninstall = function () {
    UNAS.RemoveDesktopShortcut('Audiobookshelf')
    UNAS.RemoveMenu('Audiobookshelf')
    UNAS.RemoveAppFromGroups('Audiobookshelf', 'ControlPanel')
    UNAS.RemoveAppFromApps('Audiobookshelf')
  }
  new UNAS.Menu(
    'UNAS_App_Internet_Menu',
    this.name,
    this.menuIcon,
    'Audiobookshelf',
    '',
    this.AudiobookshelfAppWindow
  )
  new UNAS.RegisterToAppGroup(
    this.name,
    'ControlPanel',
    {
      Type: 'Internet',
      Location: 1,
      Icon: this.shortcutIcon,
      Url: this.entryUrl,
    },
    {}
  )
  var OnChangeLanguage = function (e) {
    UNAS.SetMenuTitle('Audiobookshelf', UNAS._('Audiobookshelf')) //translate menu
    //UNAS.SetShortcutTitle('Audiobookshelf', UNAS._('Audiobookshelf'));
    if (typeof self.window !== 'undefined') {
      UNAS.SetWindowTitle('AudiobookshelfAppWindow', UNAS._('Audiobookshelf'))
    }
  }
  UNAS.LoadTranslation(
    '/apps/audiobookshelf/languages/Translation?v=' + this.version,
    OnChangeLanguage
  )
  UNAS.Event.addEvent('ChangeLanguage', OnChangeLanguage)
  UNAS.CreateApp(
    this.name,
    this.shortcutIcon,
    this.AudiobookshelfAppWindow,
    this.AudiobookshelfUninstall
  )
}

new AudiobookshelfApp.App()
