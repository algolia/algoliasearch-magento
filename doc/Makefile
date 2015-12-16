all: release

release:
	rm -rf _site && \
	bundle install && \
	JEKYLL_ENV=production bundle exec jekyll build && \
	cd _site && \
	git init . && \
  git add . && \
  git commit -m "Update documentation." && \
  git push git@github.com:algolia/magento.git master:gh-pages --force && \
  rm -rf .git
