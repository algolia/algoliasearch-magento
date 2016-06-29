Magento Community Website
==========================

This is the source code of the https://community.algolia.com/magento/ website. It's based on [Jekyll](http://jekyllrb.com/).

# Development

To run the website locally, you can do:

```sh
$ cd doc/
$ bundle install
$ bundle exec guard
$ open http://localhost:4000/magento/
```

# Deployment

We use gh-pages to host the built version of this website. To release it, use the following script:

```sh
$ cd doc/
$ make release
```
