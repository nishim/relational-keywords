FROM ubuntu:18.10

# chrome
RUN set -x && \
    apt update && \
    apt upgrade -y && \
    apt install -y wget curl gnupg unzip libappindicator1 fonts-liberation locales fonts-noto-cjk && \
    echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" > /etc/apt/sources.list.d/google-chrome.list && \
    wget -qO - https://dl.google.com/linux/linux_signing_key.pub | apt-key add - && \
    apt update && \
    apt install -y google-chrome-stable && \
    echo "ja_JP UTF-8" > /etc/locale.gen && \
    locale-gen

EXPOSE 9222

ENV LANG=ja_JP.utf8

CMD ["google-chrome-stable", "--headless", "--no-sandbox", "--remote-debugging-port=9222", "--remote-debugging-address=0.0.0.0", "https://www.google.com/"]
