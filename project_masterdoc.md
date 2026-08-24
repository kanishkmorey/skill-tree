# Skill Tree Master Doc

## Goal:
Development of a system where I can manage what I have learnt and organize them in form of a skill tree.

## Functional Requirements:
### Must:
- Being able to insert and edit any skill.
- Being able to insert and edit current knowledge level details, requirements for attainment and resources in any skill.
- Being able to create a branch of any skill and put new skills in it.
- Being able to easily move, deattach, attach any skill/branch to another skill/branch.
- Being able to see the whole skill tree in a visually informative manner.
- Making the whole system available cross device/network and syncronous.

### Should:
- Making the whole skill tree easily readable, editable, extensible for AI agents.

### Could:
- Introductory AI chat for setting skill up using AI and modifying existing skill tree.
- A manual setup flow.

### Won't:
- Provide knowledge other than stored to the user (except if user wants to do this through AI).
- Have or show roadmaps for different skills (as of now) / templating (except if user wants to do this through AI).

## Non-functional Requirements:
It's a personal project, currently just need to build it such that it can be hosted somewhere.

## Definitions:
### What is a skill?
Skill generally means an ability to do something well, here also it means the same a real world ability to do something, but here we will also define it in a complete sense.
A complete sense means, what exact requirements must be fullfilled in order to mark this skill as attained.

### What is a tree?
A tree is similar to the tree data type, it is an object which contains nodes, each node is an skill. Three will be a root skill through which other skills can descend, same goes for every skill in the tree.

### What are skill.requirements?
Requirements of a skill are the conditions that must be attained to call a skill as attained.

### What are skill.resources?
Resources can be articles, books, podcasts, videos, courses etc, that can be helpful for attainig a skill.

### What is skill.knowledge_level?
This are the sub abilities that are

---

# Skill Tree Phase 1

Skills and Trees will belong to a Workspace, our workspaces are managed through our authentcation provider 36Blocks. We can find the workspace the user is currently using in the user attribute it in request, it contains an object named current_company containing the details of the workspace user is in, any activity that the user does such as creation, modification is limited and is tied up to that workspace. 

## Skill
### Table structure
- skills:
id
name, text, not null
slug, text, not null, all lower case, no space(can manage through framework)
description, text
notes, text
resources, separate table
knowledge_level, separate table
workspace_id
created_by

- requirements:
id
title, text, not null
fulfilled, boolean, not null, default false
skill_id, FK->skill.id

- resources:
id
name, text, not null
type (one of: artile, book, video, audio, course, blog, rss feed etc)
url text
notes, text
status, text
workspace_id
created_by

- skill_resources:
id
skill_id, FK->skill.id
resource_id, FK->resource.id
UNIQUE (skill_id, resource_id)

- skill_knowledge
id
skill_id, FK->skill.id
title, text
status, text, one of: not-started, learning, familiar, proficient, mastered

## Tree
### Definitions

### Table structure
- trees:
id
title, text, not null
description, text
workspace_id, bigint
created_by

- tree_nodes:
id
tree_id, FK->tree.id
skill_id, FK->skill.id
parent_node_id, {
    FOREIGN KEY (tree_id, parent_node_id)
        REFERENCES tree_nodes(tree_id, id),}
    indexes:
    UNIQUE (tree_id, skill_id)
    INDEX (tree_id, parent_node_id)

## API
Base prefix: `/api/v1`. All routes are workspace-scoped via 36Blocks `current_company` (middleware + Eloquent global scope) — workspace is never part of the URL. Responses use a consistent envelope (`{ "data": ..., "meta": ... }`) via API Resource classes. Standard resources use REST CRUD; tree structure mutations get explicit action endpoints instead of generic PATCH.

### Skill endpoints
```
GET|POST     /skills
GET|PATCH|DELETE  /skills/{skill}
GET|POST     /skills/{skill}/requirements
PATCH|DELETE /requirements/{requirement}
GET|POST     /skills/{skill}/knowledge
PATCH|DELETE /knowledge/{knowledgeEntry}
```

### Resource endpoints
```
GET|POST     /resources
POST         /skills/{skill}/resources           (attach existing resource)
DELETE       /skills/{skill}/resources/{resource} (detach)
```

### Tree endpoints
```
GET|POST     /trees
GET|PATCH|DELETE /trees/{tree}
GET          /trees/{tree}/structure   → full nested tree (read-optimized, via tree-query)
POST         /trees/{tree}/nodes                  (create branch: new skill under a parent node)
POST         /trees/{tree}/nodes/{node}/attach     (attach existing skill/subtree)
POST         /trees/{tree}/nodes/{node}/move       (re-parent; target identified by skill_id, per UNIQUE(tree_id, skill_id))
DELETE       /trees/{tree}/nodes/{node}            (detach; body param: detach_only | delete_subtree)
```

### Open decisions (resolve during implementation)
- Cycle prevention logic for `move`/`attach` (parent chain check before re-parenting).
- Default behavior for `DELETE /nodes/{node}` when it has children — require explicit `detach_only`/`delete_subtree`, no silent default.
- Whether `create branch` can also attach an *existing* skill not yet in this tree, or always creates a new skill.