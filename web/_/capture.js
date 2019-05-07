'use strict'

const puppeteer = require('puppeteer')
const request = require('request-promise-native')
const fs = require('fs')

const options = {
  uri: `http://172.30.0.2:9222/json/version`,
  json: true,
  resolveWithFullResponse: true
}

const base_query = process.argv[2]
const id = process.argv[3]

const retrieve_keywords = async function(browser, query, level) {
  fs.appendFile(__dirname+'/tmp.txt', '\t'.repeat(level) + query + '\n', () => {})
  if (level == 4) {
    return
  }

  const page = await browser.newPage()
  await page.goto('https://www.google.co.jp/', {waitUntil: "domcontentloaded"})
  await page.evaluate((_q) => {
    document.querySelector('input[name="q"]').value = _q;
  }, query)
  await page.evaluate(() => {
    document.querySelector('form[action="/search"]').submit();
  })
  await page.waitForNavigation()
  await page.waitFor(8800)

  const keywords = await page.evaluate(() => {
    let arr = [];
    document.querySelectorAll('#brs .brs_col a').forEach((e) => {
      arr.push(e.innerHTML.replace(/&amp;/, '&').replace(/<[^>]+>/g, ''));
    })
    return arr;
  })

  for (let i = 0; i < keywords.length; i++) {
    await retrieve_keywords(browser, keywords[i], (level + 1))
  }

  await page.close();
}

request(options).then((res) => {
  let webSocket = res.body.webSocketDebuggerUrl;
  console.log(`WebsocketUrl: ${webSocket}`);

  (async () => {
    try {
      // initialize
      const browser = await puppeteer.connect({browserWSEndpoint: webSocket})
      await retrieve_keywords(browser, base_query, 0);
      fs.rename(__dirname+'/tmp.txt', __dirname+'/../output/' + id + '.txt', () => {})
      browser.disconnect()
    } catch(e) {
      console.log(e)
    }
  })()
})
