---
layout: default
title: How I...
---

<ul>
    {% for post in site.categories.how-i %}
    <li>
        <a href="{{ post.url }}">{{ post.title }}</a>
    </li>
    {% endfor %}
</ul>
