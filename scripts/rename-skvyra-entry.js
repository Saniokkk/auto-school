const fs = require('fs');
const path = require('path');

const distRoot = path.join(__dirname, '..', 'dist');
const branchDir = path.join(distRoot, 'skvyra');
const from = path.join(branchDir, 'skvyra.html');
const to = path.join(branchDir, 'index.html');

if (fs.existsSync(from)) {
  fs.renameSync(from, to);
} else if (!fs.existsSync(to)) {
  throw new Error(`Expected Parcel build output not found: ${from}`);
}

// Плаский alias /skvyra.html -> /skvyra/ для продакшену.
// Локально (npm start) Parcel сам роздає src/skvyra.html як справжню
// сторінку за цим шляхом, тож усі посилання на сайті (хедер/футер/модалка)
// ведуть саме на "/skvyra.html" — і в dev, і в проді. Тут ми лише
// гарантуємо, що цей самий шлях існує і на проді, просто як редирект
// на канонічний "/skvyra/" (а не дублюємо контент — щоб не плодити
// дублі для пошуковиків).
const aliasPath = path.join(distRoot, 'skvyra.html');
const aliasHtml = `<!DOCTYPE html>
<html lang="uk">
  <head>
    <meta charset="UTF-8" />
    <meta name="robots" content="noindex" />
    <meta http-equiv="refresh" content="0; url=/skvyra/" />
    <link rel="canonical" href="https://www.avtoshkoladriver.com.ua/skvyra/" />
    <title>Автошкола Сквира</title>
    <script>location.replace('/skvyra/');</script>
  </head>
  <body>
    <p>Переходимо на <a href="/skvyra/">сторінку філіалу Сквира</a>...</p>
  </body>
</html>
`;

fs.writeFileSync(aliasPath, aliasHtml, 'utf8');
