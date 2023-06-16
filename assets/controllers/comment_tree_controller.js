import { Controller } from "@hotwired/stimulus";

const COMMENT_ELEMENT = "blockquote";
const COMMENT_LEVEL_CLASS_REGEXP = /comment-level--(\d+)/;
const HIDE_NESTED_CLASS = "hide-nested";
const COLLAPSED_COMMENT_CLASS = "collapsed-tree";
const HIDDEN_DIV_CLASS_LIST = (level) =>
`section comment ${COLLAPSED_COMMENT_CLASS} comment-level--${level}`;
const COLLAPSE_INDICATOR = "comment-collapse-indicator";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  toggleCommentTree(event) {
    event.preventDefault();

    const comment = this.element;
    const isHideAction = !comment.classList.contains(HIDE_NESTED_CLASS);
    const levelMatch = comment.className.match(COMMENT_LEVEL_CLASS_REGEXP);
    const level = levelMatch ? parseInt(levelMatch[1], 10) : 1;

    let nextSibling = comment.nextElementSibling;
    // If we're expanding the tree again, skip the "x comments collapsed..." sibling element
    if (!isHideAction) {
      nextSibling = nextSibling.nextElementSibling;
    }

    let updateCount = 0;
    let lastComment;

    while (nextSibling) {
      const nextSiblingLevelMatch = nextSibling.className.match(
        COMMENT_LEVEL_CLASS_REGEXP
      );
      const isComment = !nextSibling.classList.contains(
        COLLAPSED_COMMENT_CLASS
      );

      // If next comment is a no longer a child (next comment <= comment clicked), we're done
      if (
        !nextSiblingLevelMatch ||
        parseInt(nextSiblingLevelMatch[1], 10) <= level
      ) {
        lastComment = nextSibling;
        break;
      }

      // If we're expanding the tree and we see another "x comments collapsed..." (nested tree in nested tree)
      // just delete it and continue expanding

      // TODO #284: Add functionality to preserve nested comment trees in nested comments trees instead
      // https://codeberg.org/Kbin/kbin-core/issues/284
      if (!isHideAction && !isComment) {
        nextSibling.previousElementSibling.classList.toggle(HIDE_NESTED_CLASS);
        const thisElement = nextSibling;
        nextSibling = nextSibling.nextElementSibling;
        thisElement.remove();
      } else {
        nextSibling.style.display = isHideAction ? "none" : "grid";
        nextSibling = nextSibling.nextElementSibling;
        isComment && updateCount++;
      }
    }

    comment.classList.toggle(HIDE_NESTED_CLASS);
    comment.querySelector(`.${COLLAPSE_INDICATOR}`).innerHTML = isHideAction
      ? "[+]"
      : "[-]";

    // If nothing hidden / shown, don't add a banner on the bottom
    if (updateCount < 1) {
      return;
    }

    if (isHideAction) {
      const hiddenDiv = document.createElement(COMMENT_ELEMENT);
      hiddenDiv.classList = HIDDEN_DIV_CLASS_LIST(level);
      hiddenDiv.innerText = `${updateCount} comments collapsed...`;
      comment.insertAdjacentElement("afterend", hiddenDiv);
    } else {
      comment.nextElementSibling.remove();
    }
  }
}
