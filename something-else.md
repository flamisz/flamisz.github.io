---
layout: default
title: Something else
---

<ul>
    {% for post in site.categories.something-else %}
    <li>
        <a href="{{ post.url }}">{{ post.title }}</a>
    </li>
    {% endfor %}
</ul>
