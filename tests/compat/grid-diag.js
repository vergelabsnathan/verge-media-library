const { chromium } = require('playwright');
const BASE = 'http://localhost:8888';
(async () => {
    const browser = await chromium.launch();
    const page = await browser.newPage({ viewport: { width: 1500, height: 950 } });
    page.setDefaultTimeout(180000); page.setDefaultNavigationTimeout(180000);
    const errs = []; page.on('pageerror', e => errs.push(String(e).slice(0,120)));
    page.on('console', m => { if (m.type()==='error') errs.push('console: '+m.text().slice(0,120)); });

    const ajax = [];
    page.on('response', async r => {
        if (r.url().includes('admin-ajax.php')) {
            try {
                const req = r.request().postData() || '';
                if (req.includes('query-attachments')) {
                    const body = await r.text();
                    let parsed; try { parsed = JSON.parse(body); } catch (e) {}
                    ajax.push({
                        status: r.status(),
                        query: decodeURIComponent(req).slice(0, 300),
                        success: parsed ? parsed.success : '(unparseable)',
                        count: parsed && Array.isArray(parsed.data) ? parsed.data.length : (parsed && parsed.data ? 'data:'+JSON.stringify(parsed.data).slice(0,120) : '?'),
                        raw: parsed ? '' : body.slice(0, 200),
                    });
                }
            } catch (e) {}
        }
    });

    await page.goto(BASE + '/wp-login.php', { waitUntil: 'domcontentloaded' });
    await page.fill('#user_login','admin'); await page.fill('#user_pass','password'); await page.click('#wp-submit');
    try { await page.waitForURL('**/wp-admin/**', { timeout: 120000 }); } catch(e){}
    await page.goto(BASE + '/wp-admin/upload.php?mode=grid', { waitUntil: 'domcontentloaded' });
    try { await page.waitForSelector('.attachments .attachment', { timeout: 40000 }); } catch(e){}
    await page.waitForTimeout(4000);

    const dom = await page.evaluate(() => ({
        tiles: document.querySelectorAll('.attachments .attachment').length,
        browserEl: !!document.querySelector('.attachments-browser'),
        noMedia: !!document.querySelector('.no-media'),
        spinner: !!document.querySelector('.media-frame .spinner.is-active'),
        bodyClass: document.body.className.split(' ').filter(c=>/media|eml|verge/i.test(c)).join(' '),
        collection: (() => { try { return wp.media.frame.state().get('library').length; } catch(e) { return 'n/a: '+e.message; } })(),
    }));

    console.log(JSON.stringify({ dom, ajax, errs: [...new Set(errs)].slice(0,5) }, null, 2));
    await browser.close();
})();
